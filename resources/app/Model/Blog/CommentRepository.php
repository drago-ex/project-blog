<?php declare(strict_types=1);

namespace App\Model\Blog;

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
}
