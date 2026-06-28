<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use Drago\Utils\ExtraArrayHash;


class ArticleValues extends ExtraArrayHash
{
	public const string
		Title = 'title',
		Slug = 'slug',
		Perex = 'perex',
		Content = 'content',
		Status = 'status',
		CommentsEnabled = 'comments_enabled',
		PublishedAt = 'published_at';

	public ?int $id = null;
	public string $title;
	public string $slug;
	public ?string $perex = null;
	public ?string $content = null;
	public string $status;
	public bool $comments_enabled = true;
	public ?string $published_at = null;
}
