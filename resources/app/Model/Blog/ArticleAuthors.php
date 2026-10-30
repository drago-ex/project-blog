<?php declare(strict_types=1);

namespace App\Model\Blog;

use Drago\Gravatar\EmailException;
use Drago\Gravatar\Gravatar;
use Drago\Gravatar\SizeException;


/** Names and Gravatar avatars of the authors of articles; their email addresses do not reach the templates. */
readonly class ArticleAuthors
{
	private const int AvatarSize = 80;


	public function __construct(
		private ArticleRepository $articleRepository,
		private Gravatar $gravatar,
	) {
	}


	/**
	 * @param ArticleEntity[] $articles
	 * @return array<int, array{name: string, avatar: string}> by the author id
	 * @throws EmailException
	 * @throws SizeException
	 */
	public function forArticles(array $articles): array
	{
		$ids = array_values(array_unique(array_filter(array_map(fn(ArticleEntity $article): ?int => $article->authorId, $articles))));

		$authors = [];
		foreach ($this->articleRepository->getAuthors($ids) as $id => $author) {
			$authors[$id] = [
				'name' => $author['username'],
				'avatar' => $this->gravatar->getUrl($author['email'], self::AvatarSize),
			];
		}

		return $authors;
	}
}
