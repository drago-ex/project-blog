<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use Drago\Utils\ExtraArrayHash;


class ArticleValues extends ExtraArrayHash
{
	public const string
		CategoryId = 'category_id',
		Title = 'title',
		Slug = 'slug',
		Perex = 'perex',
		Content = 'content',
		Status = 'status',
		CommentsEnabled = 'comments_enabled',
		PublishedAt = 'published_at';

	public ?int $id = null;
	public ?int $category_id = null;
	public string $title;
	public string $slug;
	public ?string $perex = null;
	public ?string $content = null;
	public string $status;
	public bool $comments_enabled = true;
	public ?\DateTimeImmutable $published_at = null;
}
