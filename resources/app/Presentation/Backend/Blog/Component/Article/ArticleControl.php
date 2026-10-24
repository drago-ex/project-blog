<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use App\Model\Blog\ArticleEntity;
use App\Model\Blog\ArticleImages;
use App\Model\Blog\ArticleRepository;
use App\Model\Blog\CategoryRepository;
use App\Presentation\Backend\Blog\Component\BaseControl;
use App\Presentation\Backend\Blog\Component\Factory;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Form\Autocomplete;
use Drago\Texy\TexyToolbar;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Utils\Html;


class ArticleControl extends BaseControl
{
	/** Largest uploaded cover image (8 MB); it is made smaller by ArticleImages. */
	private const int MaxImageSize = 8 * 1024 * 1024;


	public function __construct(
		public Factory $factory,
		private readonly ArticleRepository $articleRepository,
		private readonly ArticleImages $articleImages,
		private readonly CategoryRepository $categoryRepository,
		private readonly TexyToolbar $texyToolbar,
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

		$template->setFile(__DIR__ . '/Article.latte');
		$template->render();
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
		$defaults = (array) $item;
		$defaults[ArticleEntity::ColumnCommentsEnabled] = (bool) $item->comments_enabled;
		$form->setDefaults($defaults);

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


	/** @throws AttributeDetectionException|InvalidColumnException */
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

		$grid->addColumnDate(ArticleEntity::ColumnUpdatedAt, 'Updated at');

		// One click switches the comments of the article; the row shows the action for its current state.
		$grid->addAction(
			name: 'disallowComments',
			label: 'Disallow comments',
			callback: fn(int $id) => $this->setCommentsEnabled($id, false),
			class: 'ajax btn btn-xs btn-secondary',
			condition: fn(array $row): bool => (int) $row[ArticleEntity::ColumnCommentsEnabled] === 1,
		);

		$grid->addAction(
			name: 'allowComments',
			label: 'Allow comments',
			callback: fn(int $id) => $this->setCommentsEnabled($id, true),
			class: 'ajax btn btn-xs btn-success',
			condition: fn(array $row): bool => (int) $row[ArticleEntity::ColumnCommentsEnabled] === 0,
		);

		// A click on the row opens the form, the Edit button is then hidden by the grid.
		$grid->setRowClickAction('edit');
		$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
		$grid->addActionDelete(fn(int $id) => $this->handleDelete($id));

		return $grid;
	}


	protected function createComponentArticle(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(ArticleValues::Title, 'Title')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter title.');

		$form->addSlugInput(ArticleValues::Slug, 'Slug')
			->setRequired('Please enter slug.');

		// Category names are data, not texts: wrapped in Html they are not translated, the caption and prompt still are.
		$categories = array_map(fn(string $name): Html => Html::el()->setText($name), $this->categoryRepository->getNames());
		$form->addSelect(ArticleValues::CategoryId, 'Category', $categories)
			->setPrompt('No category');

		$form->addTextAreaInput(ArticleValues::Perex, 'Perex')
			->setNullable();

		$form->addUpload(ArticleValues::Upload, 'Upload a main image')
			->addRule($form::Image, 'The image must be JPEG, PNG, GIF or WebP.')
			->addRule($form::MaxFileSize, 'The image can have at most 8 MB.', self::MaxImageSize);

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
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	private function setCommentsEnabled(int $id, bool $enabled): void
	{
		$this->articleRepository->setCommentsEnabled($id, $enabled);
		$this->addFlashMessage($enabled ? 'Comments have been allowed.' : 'Comments have been disallowed.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
		$this['dataGrid']->redrawDataGrid();
	}


	private function processArticleForm(Form $form, ArticleValues $values): void
	{
		// the image fields are not columns, they decide the column image
		$upload = $values->upload;
		$removeImage = $values->remove_image;
		unset($values->upload, $values->remove_image);

		$oldImage = (int) $values->id > 0
			? $this->articleRepository->get((int) $values->id)->record()?->image
			: null;
		$newImage = null;

		try {
			$message = (int) $values->id > 0 ? 'Update successful.' : 'Insert successful.';

			// the list is ordered by the publication date, so a published article always has one
			if ($values->status === ArticleEntity::StatusPublished && $values->published_at === null) {
				$values->published_at = new \DateTimeImmutable;
			}

			// the signed-in user who creates the article is its author
			if ((int) $values->id === 0) {
				$userId = $this->getPresenter()->getUser()->getId();
				$values->author_id = is_numeric($userId) ? (int) $userId : null;
			}

			if ($upload?->isOk()) {
				$values->image = $newImage = $this->articleImages->save($upload);
			} elseif ($removeImage) {
				$values->image = null;
			}

			$this->articleRepository->save($values);

			if ($oldImage !== null && ($newImage !== null || $removeImage)) {
				$this->articleImages->delete($oldImage);
			}

			$this->addFlashMessage($message, Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$form->reset();
			$this->closeComponent();
			$this['dataGrid']->redrawDataGrid();

		} catch (\Throwable $e) {
			// the article was not saved, so a new image would stay unused
			$this->articleImages->delete($newImage);
			$message = match ($e->getCode()) {
				1062 => 'This article already exists.',
				default => 'Unknown status code.',
			};

			$form->addError($message);
			$this->redrawOffCanvas();
		}
	}
}
