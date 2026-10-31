<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use App\Model\Blog\ArticleEntity;
use App\Model\Blog\ArticleImages;
use App\Model\Blog\ArticleRepository;
use App\Model\Blog\CategoryEntity;
use App\Model\Blog\CategoryRepository;
use App\Model\Blog\CommentEntity;
use App\Model\Blog\CommentRepository;
use App\Presentation\Backend\Blog\Component\BaseControl;
use App\Presentation\Backend\Blog\Component\Factory;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Form\Autocomplete;
use Drago\Texy\TexyFormatter;
use Drago\Texy\TexyToolbar;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Html;


class ArticleControl extends BaseControl
{
	/** Largest uploaded cover image (8 MB); it is made smaller by ArticleImages. */
	private const int MaxImageSize = 8 * 1024 * 1024;

	/** The article whose comments a comment signal redraws (its form is not sent then). */
	private ?int $commentsArticleId = null;

	/** The comment that asks for the confirmation of its delete. */
	private ?int $confirmDeleteComment = null;


	public function __construct(
		public Factory $factory,
		private readonly ArticleRepository $articleRepository,
		private readonly ArticleImages $articleImages,
		private readonly CategoryRepository $categoryRepository,
		private readonly CommentRepository $commentRepository,
		private readonly TexyToolbar $texyToolbar,
		private readonly TexyFormatter $texyFormatter,
	) {
		parent::__construct($this->factory);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function render(): void
	{
		$template = $this->createRender();
		assert($template instanceof ArticleTemplate);

		// the edited article (also when the form is shown again with errors) shows its main image
		$id = (int) $this->getComponent('article')->getComponent('id')->getValue();
		$template->mainImage = $id > 0 ? $this->articleRepository->get($id)->record()?->image : null;

		// a new category written before a validation error stays open
		$template->newCategory = (string) $this->getComponent('article')->getComponent(ArticleValues::NewCategory)->getValue() !== '';

		// the edited article has the tab of its comments
		$template->articleId = $articleId = $this->commentsArticleId ?? $id;
		$template->comments = $comments = $articleId > 0 ? $this->commentRepository->getByArticle($articleId)->recordAll() : [];
		foreach ($comments as $comment) {
			// basic Texy like on the website, made safe by TexyFormatter
			$template->commentHtml[$comment->id] = $this->texyFormatter->toBasicHtml($comment->content);
		}

		$template->confirmDeleteComment = $this->confirmDeleteComment;

		$template->setFile(__DIR__ . '/Article.latte');
		$template->render();
	}


	/**
	 * Allows or disallows the comments of an article by a click on their state in the grid.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(ajax: true)]
	public function handleToggleComments(int $id): void
	{
		$article = $this->articleRepository->get($id)->record();
		if ($article === null) {
			$this->error();
		}

		$this->setCommentsEnabled($id, !$article->commentsEnabled);
	}


	/**
	 * Approves a comment in the tab of the article.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(ajax: true)]
	public function handleApproveComment(int $id): void
	{
		$comment = $this->getComment($id);
		$this->commentRepository->approve($id);
		$this->addFlashMessage('The comment has been approved.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
		$this->redrawComments($comment->articleId, gridChanged: true);
	}


	/**
	 * Asks in the tab of the article whether to delete a comment.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(ajax: true)]
	public function handleConfirmDeleteComment(int $id): void
	{
		$comment = $this->getComment($id);
		$this->confirmDeleteComment = $id;
		$this->redrawComments($comment->articleId);
	}


	/**
	 * Hides the confirmation of a delete.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(ajax: true)]
	public function handleCancelDeleteComment(int $id): void
	{
		$this->redrawComments($this->getComment($id)->articleId);
	}


	/**
	 * Deletes a comment in the tab of the article.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(ajax: true)]
	public function handleDeleteComment(int $id): void
	{
		$comment = $this->getComment($id);
		$this->commentRepository
			->delete(CommentEntity::PrimaryKey, $id)
			->execute();

		$this->addFlashMessage('Delete successful.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
		$this->redrawComments($comment->articleId, gridChanged: true);
	}


	/**
	 * Handles article edit.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$item = $this->articleRepository->get($id)->record();
		if ($item === null) {
			$this->error();
		}

		$form = $this->getComponent('article');
		$form->setDefaults((array) $item);

		$sendControl = $this->getFormComponent($form, 'send');
		$sendControl?->setCaption('Edit article');

		$this->redrawOffCanvas();
	}


	/** Returns the HTML of the content for the preview of the Texy toolbar. */
	#[Requires(methods: 'POST', ajax: true)]
	public function handlePreview(): void
	{
		$this->getPresenter()->sendJson($this->texyToolbar->getPreview());
	}


	/**
	 * @throws AttributeDetectionException|InvalidColumnException
	 * @throws InvalidConfigurationException
	 */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setTranslator($this->translator);
		$grid->setDataSource($this->articleRepository->getArticlesFluent())
			->setPrimaryKey(ArticleEntity::PrimaryKey);

		$grid->addColumnText(ArticleEntity::ColumnTitle, 'Title')
			->setNaturalSort()
			->setFilterText();

		$grid->addColumnText(ArticleEntity::ColumnSlug, 'Slug')
			->setNaturalSort()
			->setFilterText();

		$categories = $this->categoryRepository->getNames();
		$grid->addColumnText(
			ArticleEntity::ColumnCategoryId,
			'Category',
			sortable: false,
			formatter: fn(mixed $value): string => $categories[(int) $value] ?? '',
		)->setFilterSelect($categories, translate: false);

		$statuses = [
			ArticleEntity::StatusDraft => 'Draft',
			ArticleEntity::StatusPublished => 'Published',
		];
		$grid->addColumnText(
			ArticleEntity::ColumnStatus,
			'Status',
			formatter: fn(mixed $value): string => $this->translate($statuses[(string) $value] ?? (string) $value),
		)->setFilterSelect($statuses);

		$grid->addColumnText(
			'comments_total',
			'Comments',
			formatter: fn(mixed $value, array $row): Html => $this->formatComments(
				(int) $row[ArticleEntity::PrimaryKey],
				(int) $row[ArticleEntity::ColumnCommentsEnabled] === 1,
				(int) $value,
				(int) $row['comments_pending'],
			),
		);

		$grid->addColumnDate(ArticleEntity::ColumnUpdatedAt, 'Updated at');

		// A click on the row opens the form, the Edit button is then hidden by the grid.
		$grid->setRowClickAction('edit');
		$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
		$grid->addActionDelete(fn(int $id) => $this->handleDelete($id));

		return $grid;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws InvalidLinkException
	 */
	protected function createComponentArticle(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(ArticleValues::Title, 'Title')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter title.')
			->addRule($form::MaxLength, 'The title can have at most %d characters.', ArticleEntity::ColumnTitleSize);

		$form->addSlugInput(ArticleValues::Slug, 'Slug')
			->setRequired('Please enter slug.');

		// Category names are data, not texts: wrapped in Html they are not translated, the caption and prompt still are.
		$categories = array_map(fn(string $name): Html => Html::el()->setText($name), $this->categoryRepository->getNames());
		$form->addSelect(ArticleValues::CategoryId, 'Category', $categories)
			->setPrompt('No category');

		// a category is created right from the article; filled in, it is used instead of the select
		$form->addTextInput(ArticleValues::NewCategory, 'Name of the new category')
			->setAutocomplete(Autocomplete::Off)
			->setHtmlAttribute('placeholder', 'Name of the new category')
			->setNullable()
			->addRule($form::MaxLength, 'The category name can have at most %d characters.', CategoryEntity::ColumnNameSize);

		$form->addTextAreaInput(ArticleValues::Perex, 'Perex')
			->setNullable();

		// the Image rule and the resizing need GD (only suggested); without it the form has no upload, so it still works
		if (ArticleImages::isSupported()) {
			$form->addUpload(ArticleValues::Upload, 'Upload a main image')
				->addRule($form::Image, 'The image must be JPEG, PNG, GIF or WebP.')
				->addRule($form::MaxFileSize, 'The image can have at most 8 MB.', self::MaxImageSize)
				->addRule(
					ArticleImages::validatePixels(...),
					'The image can have at most %d megapixels.',
					ArticleImages::MaxPixels / 1_000_000,
				);
		}

		$form->addCheckbox(ArticleValues::RemoveImage, 'Remove the main image');

		$form->addTextAreaInput(ArticleValues::Content, 'Content (Texy)')
			->setHtmlAttribute('data-texy-toolbar', $this->texyToolbar->getOptions($this->link('preview!')))
			->setNullable();

		$form->addSelect(ArticleValues::Status, 'Status', [
			ArticleEntity::StatusDraft => 'Draft',
			ArticleEntity::StatusPublished => 'Published',
		])
			->setDefaultValue(ArticleEntity::StatusDraft)
			->setRequired('Please select status.');

		$form->addDateTime(ArticleValues::PublishedAt, 'Published at');

		$form->addCheckbox(ArticleValues::CommentsEnabled, 'Allow comments')
			->setDefaultValue(true);

		$form->addHidden('id')
			->setNullable()
			->addRule($form::Integer);

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processArticleForm(...);
		// errors found on the server (e.g. a reserved slug) are shown in the open offcanvas
		$form->onError[] = fn() => $this->redrawOffCanvas();
		return $form;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function deleteItem(int $id): void
	{
		$image = $this->articleRepository->get($id)->record()?->image;
		$this->articleRepository
			->delete(ArticleEntity::PrimaryKey, $id)
			->execute();

		$this->articleImages->delete($image);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getItemTitle(int $id): ?string
	{
		return $this->articleRepository
			->get($id)
			->record()
			?->title;
	}


	/**
	 * The link of the state badge that switches the comments of an article, null when it is not clickable
	 * (the permission steps of the README return null without the write privilege).
	 * @throws InvalidLinkException
	 * @phpstan-ignore return.unusedType
	 */
	private function getToggleCommentsLink(int $id): ?string
	{
		return $this->link('toggleComments!', ['id' => $id]);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	private function setCommentsEnabled(int $id, bool $enabled): void
	{
		$this->articleRepository->setCommentsEnabled($id, $enabled);
		$this->addFlashMessage($enabled ? 'Comments have been allowed.' : 'Comments have been disallowed.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
		$this['dataGrid']->redrawDataGrid();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	private function getComment(int $id): CommentEntity
	{
		$comment = $this->commentRepository->get($id)->record();
		if ($comment === null) {
			$this->error();
		}

		return $comment;
	}


	/**
	 * Redraws only the tab of the comments (and its count), so unsaved changes of the article form stay.
	 * The grid shows the numbers of comments, so it is redrawn after a change.
	 * @throws InvalidLinkException
	 */
	private function redrawComments(int $articleId, bool $gridChanged = false): void
	{
		$this->commentsArticleId = $articleId;
		$this->redrawControl('comments');
		$this->redrawControl('commentCount');
		if ($gridChanged) {
			$this['dataGrid']->redrawDataGrid();
		}
	}


	/**
	 * Whether the article allows comments, their number and the ones waiting for approval, for the grid.
	 * A click on the state switches it, so the switch is right next to what it changes.
	 * @throws InvalidLinkException
	 */
	private function formatComments(int $id, bool $enabled, int $total, int $pending): Html
	{
		$badge = Html::el('span', ['class' => 'badge rounded-pill ' . ($enabled ? 'text-bg-success' : 'text-bg-secondary')])
			->setText($this->translate($enabled ? 'Allowed' : 'Disallowed'));

		$link = $this->getToggleCommentsLink($id);
		if ($link !== null) {
			$badge->setName('a')->addAttributes([
				'href' => $link,
				'class' => $badge->getAttribute('class') . ' ajax text-decoration-none',
				'data-naja-history' => 'off',
				'title' => $this->translate($enabled ? 'Click to disallow comments' : 'Click to allow comments'),
			]);
		}

		$cell = Html::el()->addHtml($badge);

		if ($total > 0) {
			$cell->addText(' ' . $total);
		}

		if ($pending > 0) {
			$cell->addHtml(' ')->addHtml(
				Html::el('span', ['class' => 'badge rounded-pill text-bg-accent'])->setText($this->translate('%d pending', $pending)),
			);
		}

		return $cell;
	}


	/**
	 * @throws Exception
	 * @throws AttributeDetectionException
	 * @throws InvalidLinkException
	 */
	private function processArticleForm(Form $form, ArticleValues $values): void
	{
		// the image fields and the new category are not columns, they decide the columns image and category_id
		$upload = $values->upload;
		$removeImage = $values->removeImage;
		$newCategory = trim((string) $values->newCategory);
		unset($values->upload, $values->removeImage, $values->newCategory);

		$oldImage = (int) $values->id > 0
			? $this->articleRepository->get((int) $values->id)->record()?->image
			: null;
		$newImage = null;
		$message = (int) $values->id > 0 ? 'Update successful.' : 'Insert successful.';

		// the list is ordered by the publication date, so a published article always has one
		if ($values->status === ArticleEntity::StatusPublished && $values->publishedAt === null) {
			$values->publishedAt = new \DateTimeImmutable;
		}

		// the signed-in user who creates the article is its author
		if ((int) $values->id === 0) {
			$userId = $this->getPresenter()->getUser()->getId();
			$values->authorId = is_numeric($userId) ? (int) $userId : null;
		}

		// a new category is saved together with the article, so a failed save leaves no category behind
		try {
			$this->articleRepository->transaction(function () use ($values, $newCategory, $upload, $removeImage, &$newImage): void {
				if ($newCategory !== '') {
					$values->categoryId = $this->categoryRepository->getOrCreate($newCategory);
				}

				if ($upload?->isOk()) {
					$values->image = $newImage = $this->articleImages->save($upload);
				} elseif ($removeImage) {
					$values->image = null;
				}

				$this->articleRepository->save($values);
			});

		} catch (\Throwable $e) {
			// the article was not saved, so a new image would stay unused (errors show in the offcanvas by onError)
			$this->articleImages->delete($newImage);
			$form->addError(match ($e->getCode()) {
				1062 => 'This article already exists.',
				default => $this->logUnexpected($e),
			});
			return;
		}

		// the article is saved: the replaced image goes away (a file that cannot be deleted is only logged)
		if ($oldImage !== null && ($newImage !== null || $removeImage)) {
			$this->articleImages->delete($oldImage);
		}

		$this->addFlashMessage($message, Alert::Success);
		$this->addRedraw($this->snippetMessage);

		$form->reset();
		$this->closeComponent();
		$this['dataGrid']->redrawDataGrid();
	}
}
