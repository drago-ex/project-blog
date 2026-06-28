<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use App\Model\Blog\ArticleEntity;
use App\Model\Blog\CommentEntity;
use App\Presentation\BaseTemplate;


class BlogTemplate extends BaseTemplate
{
	/** @var ArticleEntity[] */
	public array $articles = [];
	public ArticleEntity $article;

	/** @var CommentEntity[] */
	public array $comments = [];
}
