<?php declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Database\Entity;


class CategoryEntity extends Entity
{
	public const string Table = 'blog_category',
		PrimaryKey = 'id',
		ColumnSlug = 'slug',
		ColumnName = 'name';

	public const int ColumnNameSize = 255;

	public int $id;
	public string $slug;
	public string $name;
}
