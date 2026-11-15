<?php declare(strict_types=1);

namespace App\Model\Blog;

use DateMalformedStringException;
use Dibi\Connection;
use Dibi\Exception;
use Drago\Database\ExtraFluent;
use Drago\Database\Table;


readonly class CommentRepository
{
	/** @var Table<CommentEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, CommentEntity::Table, CommentEntity::PrimaryKey, CommentEntity::class);
	}


	/** @return ExtraFluent<CommentEntity> */
	public function getApprovedByArticle(int $articleId): ExtraFluent
	{
		return $this->table->read('*')
			->where('%n = ?', CommentEntity::ColumnArticleId, $articleId)
			->where('%n = ?', CommentEntity::ColumnApproved, 1)
			->orderBy(CommentEntity::ColumnCreatedAt, 'ASC');
	}


	/**
	 * The number of comments waiting for approval, for the badge of the administration menu.
	 * @throws Exception
	 */
	public function countPending(): int
	{
		return $this->table->count([CommentEntity::ColumnApproved => false]);
	}


	/**
	 * All comments of an article, approved or not, in their order; for the article in the administration.
	 * @return ExtraFluent<CommentEntity>
	 */
	public function getByArticle(int $articleId): ExtraFluent
	{
		return $this->table->read('*')
			->where('%n = ?', CommentEntity::ColumnArticleId, $articleId)
			->orderBy(CommentEntity::ColumnCreatedAt, 'ASC');
	}


	/**
	 * Numbers of approved comments by article id; articles without one are missing.
	 * @param int[] $articleIds
	 * @return array<int, int>
	 */
	public function getApprovedCounts(array $articleIds): array
	{
		if ($articleIds === []) {
			return [];
		}

		/** @var array<int, int> */
		return $this->table->read('%n, COUNT(*) AS count', CommentEntity::ColumnArticleId)
			->where('%n IN %in', CommentEntity::ColumnArticleId, $articleIds)
			->where('%n = ?', CommentEntity::ColumnApproved, 1)
			->groupBy(CommentEntity::ColumnArticleId)
			->fetchPairs(CommentEntity::ColumnArticleId, 'count');
	}


	/**
	 * New comments wait for approval in the administration.
	 * @throws Exception
	 */
	public function addComment(int $articleId, string $authorName, ?string $authorEmail, string $content): void
	{
		$this->table->insert([
			CommentEntity::ColumnArticleId => $articleId,
			CommentEntity::ColumnAuthorName => $authorName,
			CommentEntity::ColumnAuthorEmail => $authorEmail,
			CommentEntity::ColumnContent => $content,
			CommentEntity::ColumnApproved => 0,
		])->execute();
	}


	/** @throws Exception */
	public function approve(int $id): void
	{
		$this->table->update([CommentEntity::ColumnApproved => 1])
			->where('%n = ?', CommentEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * Numbers of all comments of each of the last days, today included, the oldest first; a day without one is 0.
	 * @return array<string, int> Y-m-d => number
	 * @throws Exception
	 * @throws DateMalformedStringException
	 */
	public function countByDay(int $days): array
	{
		$today = new \DateTimeImmutable('today');
		$from = $today->modify('-' . ($days - 1) . ' days');

		/** @var array<string, int> $counts */
		$counts = $this->table->read('CAST(DATE(%n) AS CHAR) AS day, COUNT(*) AS count', CommentEntity::ColumnCreatedAt)
			->where('%n >= ?', CommentEntity::ColumnCreatedAt, $from)
			->groupBy('day')
			->fetchPairs('day', 'count');

		$result = [];
		for ($day = $from; $day <= $today; $day = $day->modify('+1 day')) {
			$result[$day->format('Y-m-d')] = (int) ($counts[$day->format('Y-m-d')] ?? 0);
		}

		return $result;
	}


	/**
	 * The articles with the most approved comments, the most first (the same number by title), for the dashboard.
	 * @return list<array{title: string, count: int}>
	 * @throws Exception
	 */
	public function getMostCommented(int $limit): array
	{
		$rows = $this->table->getConnection()
			->select('a.%n, COUNT(*) AS count', ArticleEntity::ColumnTitle)
			->from('%n c', CommentEntity::Table)
			->join('%n a', ArticleEntity::Table)
			->on('a.%n = c.%n', ArticleEntity::PrimaryKey, CommentEntity::ColumnArticleId)
			->where('c.%n = 1', CommentEntity::ColumnApproved)
			->groupBy('a.%n, a.%n', ArticleEntity::PrimaryKey, ArticleEntity::ColumnTitle)
			->orderBy('count DESC, a.%n ASC', ArticleEntity::ColumnTitle)
			->limit($limit)
			->fetchAll();

		return array_map(fn($row): array => [
			'title' => (string) $row[ArticleEntity::ColumnTitle],
			'count' => (int) $row['count'],
		], $rows);
	}
}
