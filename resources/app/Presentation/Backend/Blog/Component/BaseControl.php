<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component;

use Drago\Application\UI\Alert;
use Drago\Application\UI\ExtraControl;
use Drago\Component\Component;
use Drago\Component\ModalHandle;
use Drago\Component\OffcanvasHandle;
use Drago\Datagrid\DataGrid;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Application\UI\Template;
use Nette\Forms\Controls\SubmitButton;
use Tracy\Debugger;
use Tracy\ILogger;


/** @property-read BaseTemplate $template */
abstract class BaseControl extends ExtraControl implements OffcanvasHandle, ModalHandle
{
	use Component;

	public ?string $deleteTitle = null;
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
		$template->deleteTitle = $this->deleteTitle;
		return $template;
	}


	#[Requires(ajax: true)]
	public function handleOpenModal(): void
	{
		$this->redrawModal();
	}


	#[Requires(ajax: true)]
	public function handleOpenOffcanvas(): void
	{
		$this->redrawOffCanvas();
	}


	/** Opens the confirmation dialog; its form posts the id to delete(). */
	#[Requires(ajax: true)]
	public function handleDelete(int $id): void
	{
		$title = $this->getItemTitle($id);
		if ($title === null) {
			$this->error();
		}

		$this->deleteTitle = $title;
		$this['delete']->setDefaults(['id' => $id]);
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

		// an invalid id (e.g. a tampered hidden field) keeps the dialog open with the form error
		$form->onError[] = fn() => $this->redrawModal();

		return $form;
	}


	private function delete(SubmitButton $button): void
	{
		$form = $button->getForm();

		try {
			$this->deleteItem($form->getValues()['id']);

			$this->addFlashMessage('Delete successful.', Alert::Success);
			$this->addRedraw($this->snippetMessage);

			$this->closeComponent();

			$dataGrid = $this->getComponent('dataGrid');

			if ($dataGrid instanceof DataGrid) {
				$dataGrid->redrawDataGrid();
			}

		} catch (\Throwable $e) {
			// Code 1 marks a message meant for the user; anything else is unexpected and gets logged.
			if ($e->getCode() === 1) {
				$message = $e->getMessage();
			} else {
				Debugger::log($e, ILogger::EXCEPTION);
				$message = 'Unknown status code.';
			}

			$this->addFlashMessage($message, Alert::Warning);
			$this->addRedraw($this->snippetMessage);
		}
	}
}
