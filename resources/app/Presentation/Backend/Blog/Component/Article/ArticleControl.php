<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use App\Model\Blog\ArticleEntity;
use App\Model\Blog\ArticleRepository;
use App\Presentation\Backend\Blog\Component\BaseControl;
use App\Presentation\Backend\Blog\Component\Factory;
use Dibi\Exception;
use Dibi\Result;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Form\Autocomplete;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;


class ArticleControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly ArticleRepository $articleRepository,
	) {
		parent::__construct($this->factory);
	}


	/** @throws AttributeDetectionException|InvalidColumnException */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setTranslator($this->translator);
		$grid->setDataSource($this->articleRepository->getArticlesFluent())
			->setPrimaryKey(ArticleEntity::PrimaryKey);

		$grid->addColumnText(ArticleEntity::ColumnTitle, 'Title')
			->setFilterText();

		$grid->addColumnText(ArticleEntity::ColumnSlug, 'Slug')
			->setFilterText();

		$grid->addColumnText(ArticleEntity::ColumnStatus, 'Status')
			->setFilterSelect([
				ArticleEntity::StatusDraft => 'Draft',
				ArticleEntity::StatusPublished => 'Published',
			]);

		$grid->addColumnDate(ArticleEntity::ColumnUpdatedAt, 'Updated at');

		$user = $this->getPresenter()->getUser();

		if ($user->isAllowed('Backend:Blog', 'blog-write')) {
			$grid->addAction(
				label: 'Edit',
				signal: 'edit!',
				class: 'ajax btn btn-xs btn-primary',
				callback: fn(int $id) => $this->handleEdit($id),
			);

			$grid->addAction(
				label: 'Delete',
				signal: 'delete!',
				class: 'ajax btn btn-xs btn-danger',
				callback: fn(int $id) => $this->handleDelete($id),
			);
		}

		return $grid;
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Article.latte');
		$template->render();
	}


	protected function createComponentArticle(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(ArticleValues::Title, 'Title')
			->setRequired('Please enter title.')
			->setAutocomplete(Autocomplete::Off);

		$form->addTextInput(ArticleValues::Slug, 'Slug')
			->setRequired('Please enter slug.')
			->addRule($form::Pattern, 'Use lowercase letters, numbers and hyphens.', '[a-z0-9]+(?:-[a-z0-9]+)*')
			->setAutocomplete(Autocomplete::Off);

		$form->addTextAreaForm(ArticleValues::Perex, 'Perex')
			->setHtmlAttribute('rows', 4);

		$form->addTextAreaForm(ArticleValues::Content, 'Content')
			->setHtmlAttribute('rows', 14);

		$form->addSelect(ArticleValues::Status, 'Status', [
			ArticleEntity::StatusDraft => 'Draft',
			ArticleEntity::StatusPublished => 'Published',
		])
			->setRequired('Please select status.')
			->setDefaultValue(ArticleEntity::StatusDraft);

		$form->addTextInput(ArticleValues::PublishedAt, 'Published at')
			->setNullable()
			->setPlaceholder('YYYY-MM-DD HH:MM:SS')
			->setAutocomplete(Autocomplete::Off);

		$form->addCheckbox(ArticleValues::CommentsEnabled, 'Allow comments')
			->setDefaultValue(true);

		$form->addHidden('id', $this->id)
			->addRule($form::Integer)
			->setNullable();

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->success(...);
		return $form;
	}


	private function success(Form $form, ArticleValues $values): void
	{
		try {
			$message = (int) $values->id > 0 ? 'Update successful.' : 'Insert successful.';

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


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getResultRepository(int $id): Result|int|null
	{
		return $this->articleRepository
			->delete(ArticleEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getItemRepository(int $id): string|null
	{
		return $this->articleRepository
			->get($id)
			->record()
			?->title;
	}
}
