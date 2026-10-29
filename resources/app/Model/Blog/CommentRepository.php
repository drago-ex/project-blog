<?php declare(strict_types=1);

namespace App\Model\Blog;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;
use Drago\Database\ExtraFluent;


#[Table(CommentEntity::Table, CommentEntity::PrimaryKey, entity: CommentEntity::class)]
class CommentRepository
{
	/** @use Database<CommentEntity> */
	use Database;

	public function __construct(
		protected readonly Connection $connection,
	) {
	}


	/**
	 * @return ExtraFluent<CommentEntity>
	 * @throws AttributeDetectionException
	 */
	public function getApprovedByArticle(int $articleId): ExtraFluent
	{
		return $this->read('*')
			->where('%n = ?', CommentEntity::ColumnArticleId, $articleId)
			->where('%n = ?', CommentEntity::ColumnApproved, 1)
			->orderBy(CommentEntity::ColumnCreatedAt, 'ASC');
	}


	/**
	 * The number of comments waiting for approval, for the badge of the administration menu.
	 * @throws AttributeDetectionException
	 */
	public function countPending(): int
	{
		return (int) $this->read('COUNT(*)')
			->where('%n = ?', CommentEntity::ColumnApproved, 0)
			->fetchSingle();
	}


	/**
	 * All comments of an article, approved or not, in their order; for the article in the administration.
	 * @return ExtraFluent<CommentEntity>
	 * @throws AttributeDetectionException
	 */
	public function getByArticle(int $articleId): ExtraFluent
	{
		return $this->read('*')
			->where('%n = ?', CommentEntity::ColumnArticleId, $articleId)
			->orderBy(CommentEntity::ColumnCreatedAt, 'ASC');
	}


	/**
	 * Numbers of approved comments by article id; articles without one are missing.
	 * @param int[] $articleIds
	 * @return array<int, int>
	 * @throws AttributeDetectionException
	 */
	public function getApprovedCounts(array $articleIds): array
	{
		if ($articleIds === []) {
			return [];
		}

		/** @var array<int, int> */
		return $this->read('%n, COUNT(*) AS count', CommentEntity::ColumnArticleId)
			->where('%n IN %in', CommentEntity::ColumnArticleId, $articleIds)
			->where('%n = ?', CommentEntity::ColumnApproved, 1)
			->groupBy(CommentEntity::ColumnArticleId)
			->fetchPairs(CommentEntity::ColumnArticleId, 'count');
	}


	/**
	 * New comments wait for approval in the administration.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function addComment(int $articleId, string $authorName, ?string $authorEmail, string $content): void
	{
		$this->insert([
			CommentEntity::ColumnArticleId => $articleId,
			CommentEntity::ColumnAuthorName => $authorName,
			CommentEntity::ColumnAuthorEmail => $authorEmail,
			CommentEntity::ColumnContent => $content,
			CommentEntity::ColumnApproved => 0,
		])->execute();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function approve(int $id): void
	{
		$this->update([CommentEntity::ColumnApproved => 1])
			->where('%n = ?', CommentEntity::PrimaryKey, $id)
			->execute();
	}
}
