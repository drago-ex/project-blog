<?php

declare(strict_types=1);

namespace App\Model\Blog;


/** A list of published articles with what its cards show, made by ArticleListFactory. */
final readonly class ArticleList
{
	/**
	 * @param ArticleEntity[] $articles
	 * @param array<int, array{name: string, avatar: string}> $authors of the articles by the user id
	 * @param array<int, CategoryEntity> $categories with a published article, by id
	 * @param array<int, int> $commentCounts approved comments by article id, missing for none
	 */
	public function __construct(
		public array $articles,
		public array $authors,
		public array $categories,
		public array $commentCounts,
	) {
	}
}
