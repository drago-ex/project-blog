<?php declare(strict_types=1);

namespace App\Model\Blog;


/** A list of published articles with what its cards show, made by ArticleListFactory. */
final readonly class ArticleList
{
	public function __construct(
		/** @var ArticleEntity[] */
		public array $articles,
		/** @var array<int, array{name: string, avatar: string}> of the articles by the user id */
		public array $authors,
		/** @var array<int, CategoryEntity> with a published article, by id */
		public array $categories,
		/** @var array<int, int> approved comments by article id, missing for none */
		public array $commentCounts,
	) {
	}
}
