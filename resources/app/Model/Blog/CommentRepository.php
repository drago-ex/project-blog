<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Dibi\Connection;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;
use Drago\Database\ExtraFluent;


#[Table(CommentEntity::Table, CommentEntity::PrimaryKey, entity: CommentEntity::class)]
class CommentRepository
{
	/** @phpstan-use Database<CommentEntity> */
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
	 * @param array<string, mixed> $values
	 * @throws AttributeDetectionException
	 */
	public function addComment(array $values): void
	{
		$this->insert($values)->execute();
	}
}
