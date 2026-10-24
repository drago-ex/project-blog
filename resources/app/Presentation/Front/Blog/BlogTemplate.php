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

	/** @var array<int, array{name: string, avatar: string}> authors of the listed articles by the user id */
	public array $authors = [];

	/** @var array{name: string, avatar: string}|null the author of the article (detail) */
	public ?array $author = null;

	/** @var array<int, int> numbers of approved comments by article id, missing for none */
	public array $commentCounts = [];

	/** The article allows comments. */
	public bool $commentsEnabled = false;

	/** @var CommentEntity[] */
	public array $comments = [];

	/** @var array<int, string> HTML of the basic Texy of the comments by id, made safe by TexyFormatter */
	public array $commentHtml = [];
}
