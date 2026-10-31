<?php declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Database\Entity;


class ArticleEntity extends Entity
{
	public const string Table = 'blog_article',
		PrimaryKey = 'id',
		ColumnCategoryId = 'category_id',
		ColumnSlug = 'slug',
		ColumnTitle = 'title',
		ColumnPerex = 'perex',
		ColumnImage = 'image',
		ColumnContent = 'content',
		ColumnStatus = 'status',
		ColumnCommentsEnabled = 'comments_enabled',
		ColumnAuthorId = 'author_id',
		ColumnPublishedAt = 'published_at',
		ColumnCreatedAt = 'created_at',
		ColumnUpdatedAt = 'updated_at',
		StatusDraft = 'draft',
		StatusPublished = 'published';

	public const int ColumnTitleSize = 255;

	public int $id;
	public ?int $categoryId = null;
	public string $slug;
	public string $title;

	/** Short text for the article list, also the meta description. */
	public ?string $perex = null;

	/** File name of the cover image, see ArticleImages. */
	public ?string $image = null;

	/** Texy markup, converted by Drago\Texy\TexyFormatter. */
	public ?string $content = null;
	public string $status;
	public bool $commentsEnabled;

	/** The user who wrote the article; null when the account was deleted. */
	public ?int $authorId = null;
	public ?\DateTimeInterface $publishedAt = null;
	public \DateTimeInterface $createdAt;
	public \DateTimeInterface $updatedAt;
}
