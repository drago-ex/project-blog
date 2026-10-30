<?php declare(strict_types=1);

namespace App\Model\Blog;

use App\Model\User\UserEntity;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Expression;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;
use Drago\Database\ExtraFluent;


#[Table(ArticleEntity::Table, ArticleEntity::PrimaryKey, entity: ArticleEntity::class)]
class ArticleRepository
{
	/** @use Database<ArticleEntity> */
	use Database;

	public function __construct(
		private readonly Connection $connection,
	) {
	}


	/**
	 * @return ExtraFluent<ArticleEntity>
	 * @throws AttributeDetectionException
	 */
	public function getArticlesFluent(): ExtraFluent
	{
		// numbers of all and of the not yet approved comments, for the column of the administration grid
		$comments = '(SELECT COUNT(*) FROM %n c WHERE c.%n = %n.%n';
		$args = [CommentEntity::Table, CommentEntity::ColumnArticleId, ArticleEntity::Table, ArticleEntity::PrimaryKey];
		return $this->read(
			"*, $comments) AS comments_total, $comments AND c.%n = 0) AS comments_pending",
			...[...$args, ...$args, CommentEntity::ColumnApproved],
		)
			->orderBy(ArticleEntity::PrimaryKey, 'DESC');
	}


	/**
	 * What the front shows: a published article whose date has come (a future date waits for it).
	 * One condition for the list, the detail and the used categories, so they always agree.
	 */
	public static function publishedCondition(): Expression
	{
		return new Expression(
			'%n = ? AND %n <= ?',
			ArticleEntity::ColumnStatus,
			ArticleEntity::StatusPublished,
			ArticleEntity::ColumnPublishedAt,
			new \DateTimeImmutable,
		);
	}


	/**
	 * Published articles of the front, the newest first.
	 * @return ExtraFluent<ArticleEntity>
	 * @throws AttributeDetectionException
	 */
	public function getPublishedArticles(?int $categoryId = null): ExtraFluent
	{
		$fluent = $this->read('*')
			->where(self::publishedCondition())
			->orderBy(ArticleEntity::ColumnPublishedAt, 'DESC')
			->orderBy(ArticleEntity::PrimaryKey, 'DESC');

		if ($categoryId !== null) {
			$fluent->where('%n = ?', ArticleEntity::ColumnCategoryId, $categoryId);
		}

		return $fluent;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getPublishedBySlug(string $slug): ?ArticleEntity
	{
		return $this->read('*')
			->where('%n = ?', ArticleEntity::ColumnSlug, $slug)
			->where(self::publishedCondition())
			->record();
	}


	/**
	 * Names and email addresses of the authors (users of project-auth).
	 * @param list<int> $ids
	 * @return array<int, array{username: string, email: string}> by the user id
	 */
	public function getAuthors(array $ids): array
	{
		if ($ids === []) {
			return [];
		}

		$authors = [];
		$rows = $this->connection
			->select('%n, %n, %n', UserEntity::ColumnId, UserEntity::ColumnUsername, UserEntity::ColumnEmail)
			->from(UserEntity::Table)
			->where('%n IN %in', UserEntity::ColumnId, $ids)
			->fetchAll();

		foreach ($rows as $row) {
			$authors[(int) $row[UserEntity::ColumnId]] = [
				'username' => (string) $row[UserEntity::ColumnUsername],
				'email' => (string) $row[UserEntity::ColumnEmail],
			];
		}

		return $authors;
	}


	/**
	 * Allows or disallows the comments of an article (the switch in the article grid).
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function setCommentsEnabled(int $id, bool $enabled): void
	{
		$this->update([ArticleEntity::ColumnCommentsEnabled => (int) $enabled])
			->where('%n = ?', ArticleEntity::PrimaryKey, $id)
			->execute();
	}
}
