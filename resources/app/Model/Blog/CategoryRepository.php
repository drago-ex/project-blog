<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Dibi\Connection;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;
use Drago\Database\ExtraFluent;


#[Table(CategoryEntity::Table, CategoryEntity::PrimaryKey, entity: CategoryEntity::class)]
class CategoryRepository
{
	/** @use Database<CategoryEntity> */
	use Database;

	public function __construct(
		protected readonly Connection $connection,
	) {
	}


	/**
	 * @return ExtraFluent<CategoryEntity>
	 * @throws AttributeDetectionException
	 */
	public function getCategoriesFluent(): ExtraFluent
	{
		return $this->read('*')
			->orderBy(CategoryEntity::ColumnName, 'ASC');
	}


	/**
	 * Names by id, for the select of the article form and the article grid.
	 * @return array<int, string>
	 * @throws AttributeDetectionException
	 */
	public function getNames(): array
	{
		/** @var array<int, string> */
		return $this->getCategoriesFluent()
			->fetchPairs(CategoryEntity::PrimaryKey, CategoryEntity::ColumnName);
	}


	/**
	 * Categories that have a published article, for the category list of the front.
	 * @return CategoryEntity[]
	 * @throws AttributeDetectionException
	 */
	public function getUsedCategories(): array
	{
		return $this->getCategoriesFluent()
			->where(
				'%n IN (SELECT %n FROM %n WHERE %n = ?)',
				CategoryEntity::PrimaryKey,
				ArticleEntity::ColumnCategoryId,
				ArticleEntity::Table,
				ArticleEntity::ColumnStatus,
				ArticleEntity::StatusPublished,
			)
			->recordAll();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function getBySlug(string $slug): ?CategoryEntity
	{
		return $this->read('*')
			->where('%n = ?', CategoryEntity::ColumnSlug, $slug)
			->record();
	}
}
