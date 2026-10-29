<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use Drago\Utils\ExtraArrayHash;
use Nette\Http\FileUpload;


final class ArticleValues extends ExtraArrayHash
{
	public const string CategoryId = 'category_id',
		Title = 'title',
		Slug = 'slug',
		Perex = 'perex',
		Content = 'content',
		Status = 'status',
		CommentsEnabled = 'comments_enabled',
		PublishedAt = 'published_at',
		Upload = 'upload',
		RemoveImage = 'remove_image',
		NewCategory = 'new_category';

	public ?int $id = null;
	public ?int $category_id = null;
	public string $title;
	public string $slug;
	public ?string $perex = null;
	public ?string $content = null;
	public string $status;
	public bool $comments_enabled = true;
	public ?\DateTimeImmutable $published_at = null;

	/** Form fields that decide the column image; removed before saving. */
	public ?FileUpload $upload = null;
	public bool $remove_image = false;

	/** A category written in the form instead of choosing one; it decides the column category_id, removed before saving. */
	public ?string $new_category = null;

	/** Set only when they change: an uninitialized property is not saved, so an edit keeps the stored value. */
	public ?string $image;
	public ?int $author_id;
}
