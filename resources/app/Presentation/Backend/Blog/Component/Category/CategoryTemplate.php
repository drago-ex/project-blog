<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Category;

use App\Model\Blog\CategoryEntity;
use App\Presentation\Backend\Blog\Component\BaseTemplate;


class CategoryTemplate extends BaseTemplate
{
	/** @var CategoryEntity[] the categories of the list, by name; empty while the offcanvas is closed */
	public array $categories = [];

	/** The form of a category is shown instead of the list. */
	public bool $showForm = false;

	/** The category that asks for the confirmation of its delete. */
	public ?int $confirmDelete = null;
}
