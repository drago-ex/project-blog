<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use App\Model\Blog\ArticleEntity;
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


class ArticleControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly ArticleRepository $articleRepository,
		private readonly CategoryRepository $categoryRepository,
		private readonly TexyToolbar $texyToolbar,
	) {
		parent::__construct($this->factory);
	}


	public function render(): void
	{
		$template = $this->createRender();
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
		)->setFilterSelect($categories);

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

		$user = $this->getPresenter()->getUser();

		if ($user->isAllowed('Backend:Blog', 'blog-write')) {
			// A click on the row opens the form, the Edit button is then hidden by the grid.
			$grid->setRowClickAction('edit');
			$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
			$grid->addActionDelete(fn(int $id) => $this->handleDelete($id));
		}

		return $grid;
	}


	protected function createComponentArticle(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(ArticleValues::Title, 'Title')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter title.');

		$form->addTextInput(ArticleValues::Slug, 'Slug')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter slug.')
			->addRule($form::Pattern, 'Use lowercase letters, numbers and hyphens.', '[a-z0-9]+(?:-[a-z0-9]+)*');

		$form->addSelect(ArticleValues::CategoryId, 'Category', $this->categoryRepository->getNames())
			->setPrompt('No category');

		$form->addTextAreaForm(ArticleValues::Perex, 'Perex')
			->setNullable();

		$form->addTextAreaForm(ArticleValues::Content, 'Content (Texy)')
			->setNullable()
			->setHtmlAttribute('data-texy-toolbar', $this->texyToolbar->getOptions($this->link('preview!')));

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
		return $form;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function deleteItem(int $id): void
	{
		$this->articleRepository
			->delete(ArticleEntity::PrimaryKey, $id)
			->execute();
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


	private function processArticleForm(Form $form, ArticleValues $values): void
	{
		try {
			$message = (int) $values->id > 0 ? 'Update successful.' : 'Insert successful.';

			// the list is ordered by the publication date, so a published article always has one
			if ($values->status === ArticleEntity::StatusPublished && $values->published_at === null) {
				$values->published_at = new \DateTimeImmutable;
			}

			$this->articleRepository->save($values);
			$this->addFlashMessage($message, Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$form->reset();
			$this->closeComponent();
			$this->redrawControl();
			$this['dataGrid']->redrawDataGrid();

		} catch (\Throwable $e) {
			$message = match ($e->getCode()) {
				1062 => 'This article already exists.',
				default => 'Unknown status code.',
			};

			$form->addError($message);
			$this->redrawOffCanvas();
		}
	}
}
