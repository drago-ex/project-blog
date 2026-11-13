<?php declare(strict_types=1);

namespace App\Model\Blog;

use App\Model\User\UserEntity;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Expression;
use Drago\Database\ExtraFluent;
use Drago\Database\Table;


readonly class ArticleRepository
{
	/** @var Table<ArticleEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, ArticleEntity::Table, ArticleEntity::PrimaryKey, ArticleEntity::class);
	}


	/** @return ExtraFluent<ArticleEntity> */
	public function getArticlesFluent(): ExtraFluent
	{
		// numbers of all and of the not yet approved comments and the name of the author, for the columns of
		// the administration grid
		$comments = '(SELECT COUNT(*) FROM %n c WHERE c.%n = %n.%n';
		$args = [CommentEntity::Table, CommentEntity::ColumnArticleId, ArticleEntity::Table, ArticleEntity::PrimaryKey];
		return $this->table->read(
			"*, $comments) AS comments_total, $comments AND c.%n = 0) AS comments_pending,"
			. ' (SELECT u.%n FROM %n u WHERE u.%n = %n.%n) AS author_name',
			...[...$args, ...$args, CommentEntity::ColumnApproved],
			...[UserEntity::ColumnUsername, UserEntity::Table, UserEntity::ColumnId, ArticleEntity::Table, ArticleEntity::ColumnAuthorId],
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
	 */
	public function getPublishedArticles(?int $categoryId = null): ExtraFluent
	{
		$fluent = $this->table->read('*')
			->where(self::publishedCondition())
			->orderBy(ArticleEntity::ColumnPublishedAt, 'DESC')
			->orderBy(ArticleEntity::PrimaryKey, 'DESC');

		if ($categoryId !== null) {
			$fluent->where('%n = ?', ArticleEntity::ColumnCategoryId, $categoryId);
		}

		return $fluent;
	}


	/** @throws Exception */
	public function getPublishedBySlug(string $slug): ?ArticleEntity
	{
		return $this->table->read('*')
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
		$rows = $this->table->getConnection()
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
	 * Publishes articles or takes them back to drafts (the menu of the state in the article grid). A published
	 * article without a date gets the current one, or the front would not show it.
	 * @param array<int> $ids
	 * @throws Exception
	 */
	public function setStatus(array $ids, string $status): void
	{
		$this->table->update([ArticleEntity::ColumnStatus => $status])
			->where('%n IN %in', ArticleEntity::PrimaryKey, $ids)
			->execute();

		if ($status === ArticleEntity::StatusPublished) {
			$this->table->update([ArticleEntity::ColumnPublishedAt => new \DateTimeImmutable])
				->where('%n IN %in AND %n IS NULL', ArticleEntity::PrimaryKey, $ids, ArticleEntity::ColumnPublishedAt)
				->execute();
		}
	}
}
