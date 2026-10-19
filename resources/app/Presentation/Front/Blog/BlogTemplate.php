<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use App\Model\Blog\ArticleEntity;
use App\Model\Blog\CategoryEntity;
use App\Model\Blog\CommentEntity;
use App\Presentation\BaseTemplate;


class BlogTemplate extends BaseTemplate
{
	/** @var ArticleEntity[] */
	public array $articles = [];

	/** @var array<int, CategoryEntity> categories with a published article, by id */
	public array $categories = [];

	/** The selected category of the list, or the category of the article. */
	public ?CategoryEntity $category = null;
	public ArticleEntity $article;

	/** HTML of the Texy content of the article, made safe by TexyFormatter. */
	public string $content;

	/** Printed as <meta name="description"> by the front layout. */
	public ?string $description = null;

	/** The setting blog.comments_enabled allows comments (the list shows their numbers). */
	public bool $commentsAllowed = false;

	/** @var array<int, int> numbers of approved comments by article id, missing for none */
	public array $commentCounts = [];

	/** Both the setting blog.comments_enabled and the article allow comments. */
	public bool $commentsEnabled = false;

	/** @var CommentEntity[] */
	public array $comments = [];

	/** @var array<int, string> HTML of the basic Texy of the comments by id, made safe by TexyFormatter */
	public array $commentHtml = [];
}
