<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component;

use Drago\Form\Forms;
use Nette\Application\UI\Form;


/** @extends \Drago\Application\UI\Factory<Forms> */
readonly class Factory extends \Drago\Application\UI\Factory
{
	protected function createForm(): Forms
	{
		return new Forms;
	}


	public function createDelete(?int $id = null): Form
	{
		$form = $this->create();
		$form->addHidden('id', $id)
			->addRule($form::Integer)
			->setNullable();

		return $form;
	}
}
