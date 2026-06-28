<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Database\Entity;


class ArticleEntity extends Entity
{
	public const string
		Table = 'blog_article',
		PrimaryKey = 'id',
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
	public string $slug;
	public string $title;
	public ?string $perex = null;
	public ?string $content = null;
	public string $status;
	public int $comments_enabled;
	public ?string $published_at = null;
	public string $created_at;
	public string $updated_at;
}
