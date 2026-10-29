<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Category;

use Drago\Utils\ExtraArrayHash;


class CategoryValues extends ExtraArrayHash
{
	public const string Name = 'name',
		Slug = 'slug';

	public ?int $id = null;
	public string $name;
	public string $slug;
}
