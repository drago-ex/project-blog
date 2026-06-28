<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component;

use Drago\Application\UI\ExtraTemplate;


class BaseTemplate extends ExtraTemplate
{
	public string $offcanvasId;
	public string $modalId;
	public ?string $deleteTitle = null;
}
