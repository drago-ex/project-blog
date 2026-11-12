<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component;

use Drago\Form\Forms;
use Nette\Application\UI\Form;


/** @extends \Drago\Application\UI\Factory<Forms> */
readonly class Factory extends \Drago\Application\UI\Factory
{
	public function createDelete(): Form
	{
		$form = $this->create();
		// the ids of the items to delete, separated by commas
		$form->addHidden('ids')
			->setRequired()
			->addRule($form::Pattern, null, '\d+(,\d+)*');

		return $form;
	}


	protected function createForm(): Forms
	{
		return new Forms;
	}
}
