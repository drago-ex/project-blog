<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Dibi\Connection;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;
use Drago\Database\ExtraFluent;


#[Table(ArticleEntity::Table, ArticleEntity::PrimaryKey, entity: ArticleEntity::class)]
class ArticleRepository
{
	/** @phpstan-use Database<ArticleEntity> */
	use Database;

	public function __construct(
		protected readonly Connection $connection,
	) {
	}


	/**
	 * @return ExtraFluent<ArticleEntity>
	 * @throws AttributeDetectionException
	 */
	public function getArticlesFluent(): ExtraFluent
	{
		return $this->read('*')
			->orderBy(ArticleEntity::PrimaryKey, 'DESC');
	}


	/**
	 * @return ExtraFluent<ArticleEntity>
	 * @throws AttributeDetectionException
	 */
	public function getPublishedArticles(): ExtraFluent
	{
		return $this->read('*')
			->where('%n = ?', ArticleEntity::ColumnStatus, ArticleEntity::StatusPublished)
			->orderBy(ArticleEntity::ColumnPublishedAt, 'DESC')
			->orderBy(ArticleEntity::PrimaryKey, 'DESC');
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getPublishedBySlug(string $slug): ?ArticleEntity
	{
		return $this->read('*')
			->where('%n = ?', ArticleEntity::ColumnSlug, $slug)
			->where('%n = ?', ArticleEntity::ColumnStatus, ArticleEntity::StatusPublished)
			->record();
	}
}
