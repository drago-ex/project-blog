<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog;

use App\Presentation\Backend\BackendPresenter;
use App\Presentation\Backend\Blog\Component\Article\ArticleControl;
use Exception;
use Throwable;


class BlogPresenter extends BackendPresenter
{
	public function __construct(
		private readonly ArticleControl $articleControl,
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
}
