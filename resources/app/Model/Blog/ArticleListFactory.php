<?php declare(strict_types=1);

namespace App\Model\Blog;

use Dibi\Exception;
use Drago\Gravatar\EmailException;
use Drago\Gravatar\SizeException;


/**
 * The article list of the front (the blog, its categories, the home page of project-preset), so that every list
 * passes the same data to Front/Blog/@article-list.latte.
 */
readonly class ArticleListFactory
{
	public function __construct(
		private ArticleRepository $articleRepository,
		private CategoryRepository $categoryRepository,
		private CommentRepository $commentRepository,
		private ArticleAuthors $articleAuthors,
	) {
	}


	/**
	 * Published articles, the newest first, of one category or all, at most $limit of them.
	 * @throws Exception
	 * @throws EmailException
	 * @throws SizeException
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
