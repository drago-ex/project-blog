<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component;

use Drago\Application\UI\Alert;
use Drago\Application\UI\ExtraControl;
use Drago\Component\Component;
use Drago\Component\ModalHandle;
use Drago\Component\OffcanvasHandle;
use Drago\Datagrid\DataGrid;
use Drago\Permission\Access;
use Drago\Permission\AccessCheck;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Application\UI\Template;
use Nette\Forms\Controls\SubmitButton;
use Tracy\Debugger;
use Tracy\ILogger;
use Nette\Application\UI\InvalidLinkException;


/**
 * The privileges of the blog administration (resource Backend:Blog): "edit" (the forms, the row edit, the comments
 * switch, the main image), "delete", "approveComment" and "deleteComment"; isAllowed() of AccessCheck shows the buttons.
 * @property-read BaseTemplate $template
 */
abstract class BaseControl extends ExtraControl implements OffcanvasHandle, ModalHandle
{
	use Component;
	use AccessCheck;

	/** @var list<string> Titles of the items the confirmation dialog asks about */
	public array $deleteTitles = [];
	protected string $snippetMessage = 'message';


	public function __construct(
		public Factory $factory,
	) {
	}


	public function createRender(): Template
	{
		$template = $this->template;
		$template->setTranslator($this->translator);
		$template->offcanvasId = $this->getUniqueIdComponent(self::Offcanvas);
		$template->modalId = $this->getUniqueIdComponent(self::Modal);
		$template->deleteTitles = $this->deleteTitles;
		return $template;
	}


	/** Only opens the dialog, the page is enough; its form is checked. */
	#[Access]
	#[Requires(ajax: true)]
	public function handleOpenModal(): void
	{
		$this->redrawModal();
	}


	/** Only opens the offcanvas, the page is enough; its form is checked. */
	#[Access]
	#[Requires(ajax: true)]
	public function handleOpenOffcanvas(): void
	{
		$this->redrawOffCanvas();
	}


	/**
	 * Opens the confirmation dialog for the items chosen in the grid (its bulk action "delete"); the form
	 * of the dialog posts their ids to delete().
	 * @param array<mixed> $ids
	 */
	#[Requires(ajax: true)]
	public function handleDelete(array $ids): void
	{
		$titles = [];
		foreach ($ids as $id) {
			$title = is_numeric($id) && (int) $id > 0 ? $this->getItemTitle((int) $id) : null;
			if ($title !== null) {
				$titles[(int) $id] = $title;
			}
		}

		if ($titles === []) {
			$this->error();
		}

		$this->deleteTitles = array_values($titles);
		$this['delete']->setDefaults(['ids' => implode(',', array_keys($titles))]);
		$this->redrawModal();
	}


	/** Opens the offcanvas and redraws only its form (snippet offcanvas). */
	public function redrawOffCanvas(): void
	{
		$this->offCanvasComponent(self::Offcanvas);
	}


	/** Opens the modal and redraws its body (snippet content) and footer with the form (snippet modal). */
	public function redrawModal(): void
	{
		$this->modalComponent(self::Modal);
		$this->redrawControl('content');
	}


	/** Deletes the record; an exception with code 1 shows its message to the user. */
	abstract protected function deleteItem(int $id): void;


	/** Title of the record shown in the confirmation dialog, null when it does not exist. */
	abstract protected function getItemTitle(int $id): ?string;


	protected function createComponentDelete(): Form
	{
		$form = $this->factory->createDelete();
		$form->addSubmit('confirm', 'Confirm')
			->onClick[] = $this->delete(...);

		// an invalid id (e.g. a tampered hidden field) cannot be fixed in the dialog: close it with a warning
		$form->onError[] = $this->deleteFailed(...);

		return $form;
	}


	/** Logs an exception the user cannot fix (e.g. a missing PHP extension) and returns the general message. */
	protected function logUnexpected(\Throwable $e): string
	{
		Debugger::log($e, ILogger::EXCEPTION);
		return 'Unknown status code.';
	}


	/** @throws InvalidLinkException */
	private function delete(SubmitButton $button): void
	{
		$form = $button->getForm();

		try {
			foreach (explode(',', (string) $form->getValues()['ids']) as $id) {
				$this->deleteItem((int) $id);
			}

			$this->addFlashMessage('Delete successful.', Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$this->closeComponent();

		} catch (\Throwable $e) {
			// Code 1 marks a message meant for the user; anything else is unexpected and gets logged.
			$message = $e->getCode() === 1 ? $e->getMessage() : $this->logUnexpected($e);
			$this->addFlashMessage($message, Alert::Warning);
			$this->addRedraw($this->snippetMessage);
		} finally {
			// items deleted before an error are gone, so the grid is redrawn in both cases
			$dataGrid = $this->getComponent('dataGrid');
			if ($dataGrid instanceof DataGrid) {
				$dataGrid->redrawDataGrid();
			}
		}
	}


	private function deleteFailed(): void
	{
		$this->addFlashMessage('The item could not be deleted.', Alert::Warning);
		$this->addRedraw($this->snippetMessage);
		$this->closeComponent();
	}
}
