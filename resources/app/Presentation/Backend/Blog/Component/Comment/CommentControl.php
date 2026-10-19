<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Comment;

use App\Model\Blog\CommentEntity;
use App\Model\Blog\CommentRepository;
use App\Presentation\Backend\Blog\Component\BaseControl;
use App\Presentation\Backend\Blog\Component\Factory;
use Dibi\Exception;
use Dibi\Result;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Datagrid\DataGrid;
use Drago\Datagrid\Exception\InvalidColumnException;
use Nette\Utils\Html;
use Nette\Utils\Strings;


/** Comments of the articles: new ones wait here until an administrator approves or deletes them. */
class CommentControl extends BaseControl
{
	public function __construct(
		public Factory $factory,
		private readonly CommentRepository $commentRepository,
	) {
		parent::__construct($this->factory);
	}


	public function render(): void
	{
		$template = $this->createRender();
		$template->setFile(__DIR__ . '/Comment.latte');
		$template->render();
	}


	/** @throws InvalidColumnException */
	protected function createComponentDataGrid(): DataGrid
	{
		$grid = new DataGrid;
		$grid->setTranslator($this->translator);
		$grid->setDataSource($this->commentRepository->getCommentsFluent())
			->setPrimaryKey(CommentEntity::PrimaryKey);

		$grid->addColumnDate(CommentEntity::ColumnCreatedAt, 'Created at', format: 'j. n. Y H:i');

		$grid->addColumnText('article_title', 'Article')
			->setFilterText();

		$grid->addColumnText(CommentEntity::ColumnAuthorName, 'Author')
			->setFilterText();

		$grid->addColumnText(
			CommentEntity::ColumnContent,
			'Comment',
			sortable: false,
			formatter: fn(mixed $value): string => Strings::truncate((string) $value, 120),
		)->setFilterText();

		$grid->addColumnText(
			CommentEntity::ColumnApproved,
			'Status',
			formatter: fn(mixed $value): Html => (int) $value === 1
				? Html::el('span', ['class' => 'badge text-bg-success'])->setText($this->translate('Approved'))
				: Html::el('span', ['class' => 'badge text-bg-warning'])->setText($this->translate('Waiting')),
		)->setFilterSelect([
			'0' => 'Waiting',
			'1' => 'Approved',
		]);

		$user = $this->getPresenter()->getUser();

		if ($user->isAllowed('Backend:Blog', 'blog-write')) {
			$grid->addAction(
				label: 'Approve',
				signal: 'approve!',
				class: 'ajax btn btn-xs btn-success',
				callback: fn(int $id) => $this->approve($id),
				condition: fn(array $row): bool => (int) $row[CommentEntity::ColumnApproved] === 0,
			);

			$grid->addAction(
				label: 'Delete',
				signal: 'delete!',
				class: 'ajax btn btn-xs btn-danger',
				callback: fn(int $id) => $this->handleDelete($id),
			);
		}

		return $grid;
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getResultRepository(int $id): Result|int|null
	{
		return $this->commentRepository
			->delete(CommentEntity::PrimaryKey, $id)
			->execute();
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	protected function getItemRepository(int $id): string|null
	{
		$comment = $this->commentRepository->get($id)->record();
		return $comment === null
			? null
			: $comment->author_name . ': ' . Strings::truncate($comment->content, 60);
	}


	/**
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	private function approve(int $id): void
	{
		$this->commentRepository->approve($id);
		$this->addFlashMessage('The comment has been approved.', Alert::Success);
		$this->addRedraw($this->snippetMessage);
	}
}
