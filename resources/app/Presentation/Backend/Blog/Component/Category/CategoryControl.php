<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Category;

use App\Model\Blog\CategoryEntity;
use App\Model\Blog\CategoryRepository;
use App\Presentation\Backend\Blog\Component\BaseControl;
use App\Presentation\Backend\Blog\Component\Factory;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Drago\Form\Autocomplete;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;


/** Categories of the articles; deleting one leaves its articles without a category. */
class CategoryControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly CategoryRepository $categoryRepository,
	) {
		parent::__construct($this->factory);
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Category.latte');
		$template->render();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$item = $this->categoryRepository->get($id)->record();
		if ($item === null) {
			$this->error();
		}

		$form = $this->getComponent('category');
		$form->setDefaults((array) $item);

		$sendControl = $this->getFormComponent($form, 'send');
		$sendControl?->setCaption('Edit category');

		$this->redrawOffCanvas();
	}


	/** @throws AttributeDetectionException|InvalidColumnException */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setTranslator($this->translator);
		$grid->setDataSource($this->categoryRepository->getCategoriesFluent())
			->setPrimaryKey(CategoryEntity::PrimaryKey);

		$grid->addColumnText(CategoryEntity::ColumnName, 'Title')
			->setFilterText();

		$grid->addColumnText(CategoryEntity::ColumnSlug, 'Slug')
			->setFilterText();

		// A click on the row opens the form, the Edit button is then hidden by the grid.
		$grid->setRowClickAction('edit');
		$grid->addActionEdit(fn(int $id) => $this->handleEdit($id));
		$grid->addActionDelete(fn(int $id) => $this->handleDelete($id));

		return $grid;
	}


	protected function createComponentCategory(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(CategoryValues::Name, 'Title')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter title.');

		$form->addSlugInput(CategoryValues::Slug, 'Slug')
			->setRequired('Please enter slug.');

		$form->addHidden('id')
			->setNullable()
			->addRule($form::Integer);

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processCategoryForm(...);
		return $form;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function deleteItem(int $id): void
	{
		$this->categoryRepository
			->delete(CategoryEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getItemTitle(int $id): ?string
	{
		return $this->categoryRepository
			->get($id)
			->record()
			?->name;
	}


	private function processCategoryForm(Form $form, CategoryValues $values): void
	{
		try {
			$message = (int) $values->id > 0 ? 'Update successful.' : 'Insert successful.';

			$this->categoryRepository->save($values);
			$this->addFlashMessage($message, Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$form->reset();
			$this->closeComponent();
			$this['dataGrid']->redrawDataGrid();

		} catch (\Throwable $e) {
			$message = match ($e->getCode()) {
				1062 => 'This category already exists.',
				default => 'Unknown status code.',
			};

			$form->addError($message);
			$this->redrawOffCanvas();
		}
	}
}
