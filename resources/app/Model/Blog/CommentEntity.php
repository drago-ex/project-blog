<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Database\Entity;


class CommentEntity extends Entity
{
	public const string
		Table = 'blog_comment',
		PrimaryKey = 'id',
		ColumnArticleId = 'article_id',
		ColumnAuthorName = 'author_name',
		ColumnAuthorEmail = 'author_email',
		ColumnContent = 'content',
		ColumnApproved = 'approved',
		ColumnCreatedAt = 'created_at';

	public int $id;
	public int $article_id;
	public string $author_name;
	public ?string $author_email = null;
	public string $content;
	public int $approved;
	public string $created_at;
}
