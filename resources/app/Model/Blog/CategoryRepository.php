<?php declare(strict_types=1);

namespace App\Model\Blog;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Database\ExtraFluent;
use Drago\Database\Table;
use Nette\Utils\Strings;


readonly class CategoryRepository
{
	/** @var Table<CategoryEntity> */
	public Table $table;


	public function __construct(Connection $db)
	{
		$this->table = new Table($db, CategoryEntity::Table, CategoryEntity::PrimaryKey, CategoryEntity::class);
	}


	/** @return ExtraFluent<CategoryEntity> */
	public function getCategoriesFluent(): ExtraFluent
	{
		return $this->table->read('*')
			->orderBy(CategoryEntity::ColumnName, 'ASC');
	}


	/**
	 * Names by id, for the select of the article form and the article grid.
	 * @return array<int, string>
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
	 * @throws Exception
	 */
	public function getUsedCategories(): array
	{
		return $this->getCategoriesFluent()
			->where(
				'%n IN (SELECT %n FROM %n WHERE ?)',
				CategoryEntity::PrimaryKey,
				ArticleEntity::ColumnCategoryId,
				ArticleEntity::Table,
				ArticleRepository::publishedCondition(),
			)
			->recordAll();
	}


	/** @throws Exception */
	public function getBySlug(string $slug): ?CategoryEntity
	{
		return $this->table->read('*')
			->where('%n = ?', CategoryEntity::ColumnSlug, $slug)
			->record();
	}


	/**
	 * Id of the category of this name (new categories are written in the article form); a missing one is created
	 * with a slug made from the name, numbered when it is taken.
	 * @throws Exception
	 */
	public function getOrCreate(string $name): int
	{
		$name = trim($name);
		$id = $this->table->read(CategoryEntity::PrimaryKey)
			->where('%n = ?', CategoryEntity::ColumnName, $name)
			->fetchSingle();

		if ($id !== null) {
			return (int) $id;
		}

		$base = Strings::webalize($name);
		$base = $base !== '' ? $base : 'category';
		$slug = $base;
		for ($i = 2; $this->getBySlug($slug) !== null; $i++) {
			$slug = $base . '-' . $i;
		}

		$this->table->insert([CategoryEntity::ColumnName => $name, CategoryEntity::ColumnSlug => $slug])->execute();
		return $this->table->getInsertId();
	}
}
