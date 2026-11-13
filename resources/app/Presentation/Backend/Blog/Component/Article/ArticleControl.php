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
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Datagrid\Exception\InvalidConfigurationException;
use Drago\Form\Autocomplete;
use Drago\Permission\Access;
use Drago\Texy\TexyFormatter;
use Drago\Texy\TexyToolbar;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Html;


class ArticleControl extends BaseControl
{
	/** The colors of the theme the badges of the categories take in turn. */
	private const array CategoryColors = ['primary', 'info', 'warning', 'success', 'danger'];

	/** The name of the offcanvas of the comments, for its id. */
	private const string Comments = 'comments';

	/** Largest uploaded cover image (8 MB); it is made smaller by ArticleImages. */
	private const int MaxImageSize = 8 * 1024 * 1024;

	/** The article whose comments are shown in their offcanvas. */
	private ?int $commentsArticleId = null;

	/** The comment that asks for the confirmation of its delete. */
	private ?int $confirmDeleteComment = null;

	/** The article whose main image a signal redraws (its form is not sent then). */
	private ?int $imageArticleId = null;

	/** The main image asks for the confirmation of its removal. */
	private bool $confirmRemoveImage = false;


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


	/** @throws Exception */
	public function render(): void
	{
		$template = $this->createRender();
		assert($template instanceof ArticleTemplate);

		// the edited article (also when the form is shown again with errors) shows its main image
		$id = $this->imageArticleId ?? (int) $this->getComponent('article')->getComponent('id')->getValue();
		$template->mainImage = $id > 0 ? $this->articleRepository->table->get($id)->record()?->image : null;
		$template->confirmRemoveImage = $this->confirmRemoveImage;

		// a new category written before a validation error stays open
		$template->newCategory = (string) $this->getComponent('article')->getComponent(ArticleValues::NewCategory)->getValue() !== '';

		$template->articleId = $id;

		// the comments of the article chosen in the grid, in their own offcanvas
		$template->commentsId = $this->getUniqueIdComponent(self::Comments);
		if ($this->commentsArticleId !== null) {
			$template->commentsTitle = $this->getItemTitle($this->commentsArticleId);
			$template->comments = $this->commentRepository->getByArticle($this->commentsArticleId)->recordAll();
			foreach ($template->comments as $comment) {
				// basic Texy like on the website, made safe by TexyFormatter
				$template->commentHtml[$comment->id] = $this->texyFormatter->toBasicHtml($comment->content);
			}
		}

		$template->confirmDeleteComment = $this->confirmDeleteComment;

		$template->setFile(__DIR__ . '/Article.latte');
		$template->render();
	}


	/**
	 * Opens the comments of an article (the button of the grid); it only reads, the page is enough.
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Access]
	#[Requires(ajax: true)]
	public function handleComments(int $id): void
	{
		if ($this->getItemTitle($id) === null) {
			$this->error();
		}

		$this->getPresenter()->payload->offcanvas = $this->getUniqueIdComponent(self::Comments);
		$this->redrawComments($id);
	}


	/**
	 * Approves a comment of the article.
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
	 * Asks at a comment whether to delete it.
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Access('deleteComment')]
	#[Requires(ajax: true)]
	public function handleConfirmDeleteComment(int $id): void
	{
		$comment = $this->getComment($id);
		$this->confirmDeleteComment = $id;
		$this->redrawComments($comment->articleId);
	}


	/**
	 * Hides the confirmation of a delete.
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Access('deleteComment')]
	#[Requires(ajax: true)]
	public function handleCancelDeleteComment(int $id): void
	{
		$this->redrawComments($this->getComment($id)->articleId);
	}


	/**
	 * Deletes a comment of the article.
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(ajax: true)]
	public function handleDeleteComment(int $id): void
	{
		$comment = $this->getComment($id);
		$this->commentRepository->table
			->delete(CommentEntity::PrimaryKey, $id)
			->execute();

		$this->addFlashMessage('Delete successful.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
		$this->redrawComments($comment->articleId, gridChanged: true);
	}


	/**
	 * Asks on the main image of the article whether to remove it.
	 * @throws Exception
	 */
	#[Access('edit')]
	#[Requires(ajax: true)]
	public function handleConfirmRemoveImage(int $id): void
	{
		$this->confirmRemoveImage = true;
		$this->redrawMainImage($id);
	}


	/**
	 * Hides the confirmation of the removal.
	 * @throws Exception
	 */
	#[Access('edit')]
	#[Requires(ajax: true)]
	public function handleCancelRemoveImage(int $id): void
	{
		$this->redrawMainImage($id);
	}


	/**
	 * Removes the main image of the article at once, without saving its form.
	 * @throws Exception
	 */
	#[Access('edit')]
	#[Requires(ajax: true)]
	public function handleRemoveImage(int $id): void
	{
		$image = $this->redrawMainImage($id);
		if ($image !== null) {
			$this->articleRepository->table->update([ArticleEntity::ColumnImage => null])
				->where('%n = ?', ArticleEntity::PrimaryKey, $id)
				->execute();

			// a file that cannot be deleted is only logged, the article has no image any more
			$this->articleImages->delete($image);
		}

		$this->addFlashMessage('The main image has been removed.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
	}


	/**
	 * Handles article edit.
	 * @throws Exception
	 */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$item = $this->articleRepository->table->get($id)->record();
		if ($item === null) {
			$this->error();
		}

		$form = $this->getComponent('article');
		$form->setDefaults((array) $item);

		$sendControl = $this->getFormComponent($form, 'send');
		$sendControl?->setCaption('Edit article');

		$this->redrawOffCanvas();
	}


	/**
	 * Publishes an article or takes it back to a draft (the menu of the state in the grid); a published article
	 * without a date gets today.
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	#[Requires(methods: 'POST', ajax: true)]
	public function handlePublish(int $id, bool $publish = true): void
	{
		$this->articleRepository->setStatus([$id], $publish ? ArticleEntity::StatusPublished : ArticleEntity::StatusDraft);
		$this->addFlashMessage(
			$publish ? 'The article has been published.' : 'The article has been taken back to a draft.',
			Alert::Success,
		);
		$this->addRedraw($this->snippetMessage);
		$this['dataGrid']->redrawDataGrid();
	}


	/** Returns the HTML of the content for the preview of the Texy toolbar; it only reads, the page is enough. */
	#[Access]
	#[Requires(methods: 'POST', ajax: true)]
	public function handlePreview(): void
	{
		$this->getPresenter()->sendJson($this->texyToolbar->getPreview());
	}


	/**
	 * @throws InvalidColumnException
	 * @throws InvalidConfigurationException
	 */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setTranslator($this->translator);
		$grid->setFilterMode('inline');
		$grid->setDataSource($this->articleRepository->getArticlesFluent())
			->setPrimaryKey(ArticleEntity::PrimaryKey);

		$grid->addColumnText(
			ArticleEntity::ColumnTitle,
			'Title',
			formatter: fn(mixed $value, array $row): Html => $this->formatTitle((string) $value, $row[ArticleEntity::ColumnImage]),
		)
			->setNaturalSort()
			->setFilterText();

		$grid->addColumnText(ArticleEntity::ColumnSlug, 'Slug')
			->setFilterText();

		// a category is a badge; its color follows its place in the list, so it stays the same on every page
		$categories = $this->categoryRepository->getNames();
		$badges = [];
		foreach (array_keys($categories) as $index => $id) {
			$badges[$id] = [$categories[$id], self::CategoryColors[$index % count(self::CategoryColors)]];
		}

		$grid->addColumnText(ArticleEntity::ColumnCategoryId, 'Category', sortable: false)
			->setBadges($badges, translate: false)
			->setFilterSelect($categories, translate: false);

		// the number of comments is a button that opens them; the ones waiting for approval stand next to it
		$grid->addColumnText(
			'comments_total',
			'Comments',
			formatter: fn(mixed $value, array $row): Html => $this->formatComments((int) $row[ArticleEntity::PrimaryKey], (int) $value, (int) $row['comments_pending']),
		);

		$grid->addColumnDate(ArticleEntity::ColumnPublishedAt, 'Published at', format: 'j. n. Y H:i')
			->setFilterDate(range: true);

		$grid->addColumnText(
			'author_name',
			'Author',
			formatter: fn(mixed $value): Html => $this->formatAuthor((string) $value),
		);

		// the status is a badge that opens a menu of the states; without the privilege it is only read
		$grid->addColumnText(ArticleEntity::ColumnStatus, 'Status')
			->setState(
				'publish',
				fn(int $id, int|string $status) => $this->handlePublish($id, $status === ArticleEntity::StatusPublished),
				[
					ArticleEntity::StatusPublished => ['Published', 'success'],
					ArticleEntity::StatusDraft => ['Draft', 'secondary'],
				],
				enabled: $this->isAllowed('publish'),
			)
			->setFilterSelect([
				ArticleEntity::StatusDraft => 'Draft',
				ArticleEntity::StatusPublished => 'Published',
			]);

		// The actions stand in the bar under the table and work with the rows chosen by their checkboxes
		// or by a click anywhere on a row; Edit is for one row.
		$grid->setRowClickAction('select');
		$grid->addBulkActionEdit(fn(int $id) => $this->handleEdit($id))
			->setVisible($this->isAllowed('edit'));
		$grid->addBulkActionExport()
			->setVisible($this->isAllowed('export'));
		$grid->addBulkActionDelete(fn(array $ids) => $this->handleDelete($ids))
			->setVisible($this->isAllowed('delete'));

		// "Export" under the table: all rows of the current filters
		$grid->setExport('articles.csv', button: $this->isAllowed('export'));

		return $grid;
	}


	/** @throws InvalidLinkException */
	#[Access('edit')]
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

		$form->addUpload(ArticleValues::Upload, 'Upload a main image')
			->addRule($form::Image, 'The image must be JPEG, PNG, GIF or WebP.')
			->addRule($form::MaxFileSize, 'The image can have at most 8 MB.', self::MaxImageSize)
			->addRule(
				ArticleImages::validatePixels(...),
				'The image can have at most %d megapixels.',
				ArticleImages::MaxPixels / 1_000_000,
			);

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


	/** @throws Exception */
	protected function deleteItem(int $id): void
	{
		$image = $this->articleRepository->table->get($id)->record()?->image;
		$this->articleRepository->table
			->delete(ArticleEntity::PrimaryKey, $id)
			->execute();

		$this->articleImages->delete($image);
	}


	/** @throws Exception */
	protected function getItemTitle(int $id): ?string
	{
		return $this->articleRepository->table
			->get($id)
			->record()
			?->title;
	}


	/** @throws Exception */
	private function getComment(int $id): CommentEntity
	{
		$comment = $this->commentRepository->table->get($id)->record();
		if ($comment === null) {
			$this->error();
		}

		return $comment;
	}


	/**
	 * Redraws the offcanvas of the comments. The grid shows the numbers of comments, so it is redrawn after
	 * a change.
	 * @throws InvalidLinkException
	 */
	private function redrawComments(int $articleId, bool $gridChanged = false): void
	{
		$this->commentsArticleId = $articleId;
		$this->redrawControl('comments');
		$this->redrawControl('commentsTitle');
		if ($gridChanged) {
			$this['dataGrid']->redrawDataGrid();
		}
	}


	/**
	 * Redraws only the main image of the article, so unsaved changes of its form stay; returns the image.
	 * @throws Exception
	 */
	private function redrawMainImage(int $id): ?string
	{
		$article = $this->articleRepository->table->get($id)->record();
		if ($article === null) {
			$this->error();
		}

		$this->imageArticleId = $id;
		$this->redrawControl('mainImage');
		return $article->image;
	}


	/** The cell of the title in the grid: a small main image (a placeholder without one) and the title. */
	private function formatTitle(string $title, mixed $image): Html
	{
		$thumb = is_string($image) && $image !== ''
			? Html::el('img', [
				'src' => $this->getPresenter()->getHttpRequest()->getUrl()->getBasePath() . ArticleImages::Path . '/' . $image,
				'alt' => '',
				'width' => 40,
				'height' => 40,
				'loading' => 'lazy',
				'class' => 'flex-shrink-0 rounded object-fit-cover bg-body-tertiary',
			])
			: Html::el('span', ['class' => 'avatar rounded', 'style' => '--cs-avatar-size: 2.5rem'])
				->addHtml(Html::el('i', ['class' => 'bi bi-image', 'aria-hidden' => 'true']));

		return Html::el('div', ['class' => 'd-flex align-items-center gap-2'])
			->addHtml($thumb)
			->addHtml(Html::el('span', ['class' => 'fw-semibold text-body-emphasis'])->setText($title));
	}


	/** The cell of the author in the grid: an icon of a person and the name; empty without an author. */
	private function formatAuthor(string $name): Html
	{
		if ($name === '') {
			return Html::el();
		}

		return Html::el('div', ['class' => 'd-flex align-items-center gap-2 text-nowrap'])
			->addHtml(
				Html::el('span', ['class' => 'avatar avatar-sm'])
					->addHtml(Html::el('i', ['class' => 'bi bi-person', 'aria-hidden' => 'true'])),
			)
			->addHtml(Html::el('span')->setText($name));
	}


	/**
	 * The cell of the comments in the grid: a button with their number that opens them in the offcanvas (gray
	 * without comments) and, next to it, the number of the ones waiting for approval.
	 * @throws InvalidLinkException
	 */
	private function formatComments(int $id, int $total, int $pending): Html
	{
		$label = $this->translate('Comments: %d', $total);
		$cell = Html::el('div', ['class' => 'd-flex align-items-center gap-2 text-nowrap'])->addHtml(
			Html::el('a', [
				'href' => $this->link('comments!', ['id' => $id]),
				'class' => 'ajax btn btn-xs btn-subtle ' . ($total > 0 ? 'theme-primary' : 'theme-secondary'),
				'data-naja-history' => 'off',
				'data-tooltip' => true,
				'aria-label' => $label,
			])
				->addHtml(Html::el('i', ['class' => 'bi bi-chat me-1', 'aria-hidden' => 'true']))
				->addText((string) $total),
		);

		if ($pending > 0) {
			$cell->addHtml(
				Html::el('span', ['class' => 'badge badge-subtle theme-warning'])->setText($this->translate('%d pending', $pending)),
			);
		}

		return $cell;
	}


	/**
	 * @throws Exception
	 * @throws InvalidLinkException
	 */
	private function processArticleForm(Form $form, ArticleValues $values): void
	{
		// the upload and the new category are not columns, they decide the columns image and category_id
		$upload = $values->upload;
		$newCategory = trim((string) $values->newCategory);
		unset($values->upload, $values->newCategory);

		$oldImage = (int) $values->id > 0
			? $this->articleRepository->table->get((int) $values->id)->record()?->image
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
			$this->articleRepository->table->transaction(function () use ($values, $newCategory, $upload, &$newImage): void {
				if ($newCategory !== '') {
					$values->categoryId = $this->categoryRepository->getOrCreate($newCategory);
				}

				if ($upload?->isOk()) {
					$values->image = $newImage = $this->articleImages->save($upload);
				}

				$this->articleRepository->table->save($values);
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
		if ($oldImage !== null && $newImage !== null) {
			$this->articleImages->delete($oldImage);
		}

		$this->addFlashMessage($message, Alert::Success);
		$this->addRedraw($this->snippetMessage);

		$form->reset();
		$this->closeComponent();
		$this['dataGrid']->redrawDataGrid();
	}
}
