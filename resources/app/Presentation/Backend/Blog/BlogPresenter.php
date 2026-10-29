<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog;

use App\Presentation\Backend\BackendPresenter;
use App\Presentation\Backend\Blog\Component\Article\ArticleControl;
use App\Presentation\Backend\Blog\Component\Category\CategoryControl;
use Exception;
use Throwable;


/** Articles with their comments (default) and categories (categories). */
class BlogPresenter extends BackendPresenter
{
	public function __construct(
		private readonly ArticleControl $articleControl,
		private readonly CategoryControl $categoryControl,
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
}
