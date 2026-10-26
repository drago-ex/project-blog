<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Attr\AttributeDetectionException;


/**
 * The article list of the front (the blog, its categories, the home page of project-preset), so that every list
 * passes the same data to Front/Blog/@article-list.latte.
 */
class ArticleListFactory
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
		private readonly CategoryRepository $categoryRepository,
		private readonly CommentRepository $commentRepository,
		private readonly ArticleAuthors $articleAuthors,
	) {
	}


	/**
	 * Published articles, the newest first, of one category or all, at most $limit of them.
	 * @throws AttributeDetectionException
	 */
	public function create(?int $categoryId = null, ?int $limit = null): ArticleList
	{
		$fluent = $this->articleRepository->getPublishedArticles($categoryId);
		if ($limit !== null) {
			$fluent->limit($limit);
		}

		$articles = $fluent->recordAll();

		return new ArticleList(
			articles: $articles,
			authors: $this->articleAuthors->forArticles($articles),
			categories: array_column($this->categoryRepository->getUsedCategories(), null, CategoryEntity::PrimaryKey),
			commentCounts: $this->commentRepository->getApprovedCounts(array_column($articles, ArticleEntity::PrimaryKey)),
		);
	}
}
