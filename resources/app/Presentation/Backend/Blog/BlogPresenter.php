<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog;

use App\Presentation\Backend\BackendPresenter;
use App\Presentation\Backend\Blog\Component\Article\ArticleControl;
use App\Presentation\Backend\Blog\Component\Category\CategoryControl;
use App\Presentation\Backend\Blog\Component\Comment\CommentControl;
use Exception;
use Throwable;


/** Articles (default), categories (categories) and the approval of comments (comments). */
class BlogPresenter extends BackendPresenter
{
	public function __construct(
		private readonly ArticleControl $articleControl,
		private readonly CategoryControl $categoryControl,
		private readonly CommentControl $commentControl,
	) {
		parent::__construct();
	}


	/** @throws Throwable|Exception */
	protected function createComponentArticles(): ArticleControl
	{
		$control = $this->articleControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	/** @throws Throwable|Exception */
	protected function createComponentCategories(): CategoryControl
	{
		$control = $this->categoryControl;
		$control->translator = $this->getTranslator();
		return $control;
	}


	/** @throws Throwable|Exception */
	protected function createComponentComments(): CommentControl
	{
		$control = $this->commentControl;
		$control->translator = $this->getTranslator();
		return $control;
	}
}
