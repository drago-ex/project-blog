<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Category;

use App\Model\Blog\CategoryEntity;
use App\Model\Blog\CategoryRepository;
use App\Presentation\Backend\Blog\Component\BaseControl;
use App\Presentation\Backend\Blog\Component\Factory;
use Dibi\Exception;
use Drago\Application\UI\Alert;
use Drago\Form\Autocomplete;
use Drago\Permission\Access;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;


/**
 * Categories of the articles in an offcanvas opened from the heading of the articles: the list and the form of
 * a category take turns in it. Deleting a category leaves its articles without one.
 */
class CategoryControl extends BaseControl
{
	/** @var array<callable(): void> Called after a category is saved or deleted (the article grid shows the names). */
	public array $onChange = [];

	/** The offcanvas was opened by a signal of this request, so the list is loaded. */
	private bool $open = false;

	/** The form of a category is shown instead of the list. */
	private bool $showForm = false;

	/** The category that asks for the confirmation of its delete. */
	private ?int $confirmDelete = null;


	public function __construct(
		public Factory $factory,
		private readonly CategoryRepository $categoryRepository,
	) {
		parent::__construct($this->factory);
	}


	/** @throws Exception */
	public function render(): void
	{
		$template = $this->createRender();
		assert($template instanceof CategoryTemplate);
		$template->showForm = $this->showForm;
		$template->confirmDelete = $this->confirmDelete;
		if ($this->open && !$this->showForm) {
			$template->categories = $this->categoryRepository->getCategoriesFluent()->recordAll();
		}

		$template->setFile(__DIR__ . '/Category.latte');
		$template->render();
	}


	/** Opens the list of the categories (also the way back from the form); it only reads, the page is enough. */
	#[Access]
	#[Requires(ajax: true)]
	public function handleOpen(): void
	{
		$this->redrawList();
	}


	/** Shows the empty form of a new category. */
	#[Access('edit')]
	#[Requires(ajax: true)]
	public function handleAdd(): void
	{
		$this->redrawForm();
	}


	/** @throws Exception */
	#[Requires(ajax: true)]
	public function handleEdit(int $id): void
	{
		$item = $this->categoryRepository->table->get($id)->record();
		if ($item === null) {
			$this->error();
		}

		$form = $this->getComponent('category');
		$form->setDefaults((array) $item);

		$sendControl = $this->getFormComponent($form, 'send');
		$sendControl?->setCaption('Edit category');

		$this->redrawForm();
	}


	/**
	 * Asks at a category in the list whether to delete it.
	 * @throws Exception
	 */
	#[Access('delete')]
	#[Requires(ajax: true)]
	public function handleConfirmDelete(int $id): void
	{
		if ($this->getItemTitle($id) === null) {
			$this->error();
		}

		$this->confirmDelete = $id;
		$this->redrawList();
	}


	/** Hides the confirmation of a delete. */
	#[Access('delete')]
	#[Requires(ajax: true)]
	public function handleCancelDelete(): void
	{
		$this->redrawList();
	}


	/**
	 * Deletes a category confirmed in the list.
	 * @throws Exception
	 */
	#[Access('delete')]
	#[Requires(ajax: true)]
	public function handleRemove(int $id): void
	{
		if ($this->getItemTitle($id) === null) {
			$this->error();
		}

		$this->deleteItem($id);
		$this->addFlashMessage('Delete successful.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
		$this->redrawList();
		$this->onChange();
	}


	#[Access('edit')]
	protected function createComponentCategory(): Form
	{
		$form = $this->factory->create();
		$form->addTextInput(CategoryValues::Name, 'Title')
			->setAutocomplete(Autocomplete::Off)
			->setRequired('Please enter title.')
			->addRule($form::MaxLength, 'The title can have at most %d characters.', CategoryEntity::ColumnNameSize);

		$form->addSlugInput(CategoryValues::Slug, 'Slug')
			->setRequired('Please enter slug.');

		$form->addHidden('id')
			->setNullable()
			->addRule($form::Integer);

		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->processCategoryForm(...);
		// errors found on the server (e.g. a taken slug) are shown in the open offcanvas
		$form->onError[] = fn() => $this->redrawForm();
		return $form;
	}


	/** @throws Exception */
	protected function deleteItem(int $id): void
	{
		$this->categoryRepository->table
			->delete(CategoryEntity::PrimaryKey, $id)
			->execute();
	}


	/** @throws Exception */
	protected function getItemTitle(int $id): ?string
	{
		return $this->categoryRepository->table
			->get($id)
			->record()
			?->name;
	}


	private function processCategoryForm(Form $form, CategoryValues $values): void
	{
		try {
			$message = (int) $values->id > 0 ? 'Update successful.' : 'Insert successful.';

			$this->categoryRepository->table->save($values);
			$this->addFlashMessage($message, Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$form->reset();
			$this->redrawList();
			$this->onChange();

		} catch (\Throwable $e) {
			// shown in the open offcanvas by onError
			$form->addError(match ($e->getCode()) {
				1062 => 'This category already exists.',
				default => $this->logUnexpected($e),
			});
		}
	}


	/** Shows the list of the categories in the offcanvas (opened, if it is not yet). */
	private function redrawList(): void
	{
		$this->open = true;
		$this->showForm = false;
		$this->redrawOffCanvas();
	}


	/** Shows the form of a category in the offcanvas instead of the list. */
	private function redrawForm(): void
	{
		$this->open = true;
		$this->showForm = true;
		$this->redrawOffCanvas();
	}
}
