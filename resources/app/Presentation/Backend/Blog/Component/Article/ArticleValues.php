<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use Drago\Utils\ExtraArrayHash;
use Nette\Http\FileUpload;


final class ArticleValues extends ExtraArrayHash
{
	public const string CategoryId = 'categoryId',
		Title = 'title',
		Slug = 'slug',
		Perex = 'perex',
		Content = 'content',
		Status = 'status',
		CommentsEnabled = 'commentsEnabled',
		PublishedAt = 'publishedAt',
		Upload = 'upload',
		RemoveImage = 'removeImage',
		NewCategory = 'newCategory';

	public ?int $id = null;
	public ?int $categoryId = null;
	public string $title;
	public string $slug;
	public ?string $perex = null;
	public ?string $content = null;
	public string $status;
	public bool $commentsEnabled = true;
	public ?\DateTimeImmutable $publishedAt = null;

	/** Form fields that decide the column image; removed before saving. */
	public ?FileUpload $upload = null;
	public bool $removeImage = false;

	/** A category written in the form instead of choosing one; it decides the column category_id, removed before saving. */
	public ?string $newCategory = null;

	/** Set only when they change: an uninitialized property is not saved, so an edit keeps the stored value. */
	public ?string $image;
	public ?int $authorId;
}
