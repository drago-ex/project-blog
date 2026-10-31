<?php declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Database\Entity;


class CommentEntity extends Entity
{
	public const string Table = 'blog_comment',
		PrimaryKey = 'id',
		ColumnArticleId = 'article_id',
		ColumnAuthorName = 'author_name',
		ColumnAuthorEmail = 'author_email',
		ColumnContent = 'content',
		ColumnApproved = 'approved',
		ColumnCreatedAt = 'created_at';

	public const int ColumnAuthorNameSize = 100,
		ColumnAuthorEmailSize = 254;

	public int $id;
	public int $articleId;
	public string $authorName;
	public ?string $authorEmail = null;
	public string $content;
	public bool $approved;
	public \DateTimeInterface $createdAt;
}
