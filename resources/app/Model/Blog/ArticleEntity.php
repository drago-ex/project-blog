<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Database\Entity;


class ArticleEntity extends Entity
{
	public const string
		Table = 'blog_article',
		PrimaryKey = 'id',
		ColumnCategoryId = 'category_id',
		ColumnSlug = 'slug',
		ColumnTitle = 'title',
		ColumnPerex = 'perex',
		ColumnContent = 'content',
		ColumnStatus = 'status',
		ColumnCommentsEnabled = 'comments_enabled',
		ColumnPublishedAt = 'published_at',
		ColumnCreatedAt = 'created_at',
		ColumnUpdatedAt = 'updated_at',
		StatusDraft = 'draft',
		StatusPublished = 'published';

	public int $id;
	public ?int $category_id = null;
	public string $slug;
	public string $title;

	/** Short text for the article list, also the meta description. */
	public ?string $perex = null;

	/** Texy markup, converted by Drago\Texy\TexyFormatter. */
	public ?string $content = null;
	public string $status;
	public int $comments_enabled;
	public ?\DateTimeInterface $published_at = null;
	public \DateTimeInterface $created_at;
	public \DateTimeInterface $updated_at;
}
