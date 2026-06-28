<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use App\Model\Blog\ArticleRepository;
use App\Model\Blog\CommentRepository;
use App\Presentation\BasePresenter;
use Drago\Attr\AttributeDetectionException;


/** @property-read BlogTemplate $template */
final class BlogPresenter extends BasePresenter
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
		private readonly CommentRepository $commentRepository,
	) {
		parent::__construct();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function renderDefault(): void
	{
		$this->template->articles = $this->articleRepository
			->getPublishedArticles()
			->recordAll();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function renderDetail(string $slug): void
	{
		$article = $this->articleRepository->getPublishedBySlug($slug);
		if ($article === null) {
			$this->error('Article not found');
		}

		$this->template->article = $article;
		$this->template->comments = $article->comments_enabled
			? $this->commentRepository->getApprovedByArticle($article->id)->recordAll()
			: [];
	}
}
