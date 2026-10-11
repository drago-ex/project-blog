<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component;

use Drago\Form\Forms;
use Nette\Application\UI\Form;


/** @extends \Drago\Application\UI\Factory<Forms> */
readonly class Factory extends \Drago\Application\UI\Factory
{
	public function createDelete(?int $id = null): Form
	{
		$form = $this->create();
		$form->addHidden('id', $id)
			->setNullable()
			->addRule($form::Integer);

		return $form;
	}


	protected function createForm(): Forms
	{
		return new Forms;
	}
}
