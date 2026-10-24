<?php

declare(strict_types=1);

namespace App\Model\Blog;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;
use Drago\Database\ExtraFluent;
use Nette\Utils\Strings;


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
	 * Categories that have a published article, for the category pills and the article cards of the front.
	 * @return CategoryEntity[]
	 * @throws AttributeDetectionException
	 */
	public function getUsedCategories(): array
	{
		return $this->getCategoriesFluent()
			->where(
				'%n IN (SELECT %n FROM %n WHERE %n = ? AND %n <= ?)',
				CategoryEntity::PrimaryKey,
				ArticleEntity::ColumnCategoryId,
				ArticleEntity::Table,
				ArticleEntity::ColumnStatus,
				ArticleEntity::StatusPublished,
				ArticleEntity::ColumnPublishedAt,
				new \DateTimeImmutable,
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


	/**
	 * Id of the category of this name (new categories are written in the article form); a missing one is created
	 * with a slug made from the name, numbered when it is taken.
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function getOrCreate(string $name): int
	{
		$name = trim($name);
		$id = $this->read(CategoryEntity::PrimaryKey)
			->where('%n = ?', CategoryEntity::ColumnName, $name)
			->fetchSingle();

		if ($id !== null) {
			return (int) $id;
		}

		// webalize() needs ext-intl (only suggested); without it letters with accents are left out of the slug
		$base = extension_loaded('intl')
			? Strings::webalize($name)
			: trim((string) preg_replace('~[^a-z0-9]+~', '-', strtolower($name)), '-');
		$base = $base !== '' ? $base : 'category';
		$slug = $base;
		for ($i = 2; $this->getBySlug($slug) !== null; $i++) {
			$slug = $base . '-' . $i;
		}

		$this->insert([CategoryEntity::ColumnName => $name, CategoryEntity::ColumnSlug => $slug])->execute();
		return $this->getInsertId();
	}
}
