<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use App\Model\Blog\ArticleAuthors;
use App\Model\Blog\ArticleEntity;
use App\Model\Blog\ArticleRepository;
use App\Model\Blog\CategoryEntity;
use App\Model\Blog\CategoryRepository;
use App\Model\Blog\CommentRepository;
use App\Presentation\BasePresenter;
use App\Presentation\Sign\RateLimiter;
use Drago\Application\UI\Alert;
use Drago\Attr\AttributeDetectionException;
use Drago\Form\Autocomplete;
use Drago\Form\Forms;
use Drago\Texy\TexyFormatter;
use Drago\Texy\TexyToolbar;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Form;
use Nette\Http\IRequest;


/**
 * Published articles at /blog, by category at /blog/kategorie/slug and an article by its slug at /blog/slug
 * (BlogRouter), with comments that anyone may write and an administrator approves.
 * @property-read BlogTemplate $template
 */
final class BlogPresenter extends BasePresenter
{
	/** Comments from one IP address in the window, against flooding. */
	private const int CommentLimit = 5;
	private const int CommentWindow = 600;

	private ArticleEntity $article;


	public function __construct(
		private readonly ArticleRepository $articleRepository,
		private readonly ArticleAuthors $articleAuthors,
		private readonly CategoryRepository $categoryRepository,
		private readonly CommentRepository $commentRepository,
		private readonly TexyFormatter $texyFormatter,
		private readonly TexyToolbar $texyToolbar,
		private readonly RateLimiter $rateLimiter,
		private readonly IRequest $httpRequest,
	) {
		parent::__construct();
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function actionDetail(string $slug): void
	{
		$article = $this->articleRepository->getPublishedBySlug($slug);
		if ($article === null) {
			$this->error('Article not found');
		}

		$this->article = $article;
	}


	/** Returns the HTML of a comment for the preview of the Texy toolbar. */
	#[Requires(methods: 'POST', ajax: true)]
	public function handleCommentPreview(): void
	{
		$this->sendJson($this->texyToolbar->getBasicPreview());
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function renderDefault(): void
	{
		$this->setArticleList(null);
	}


	/**
	 * Published articles of a category at /blog/kategorie/slug, with the template of the list.
	 * @throws AttributeDetectionException
	 */
	public function renderCategory(string $category): void
	{
		$entity = $this->categoryRepository->getBySlug($category);
		if ($entity === null) {
			$this->error('Category not found');
		}

		$this->setArticleList($entity);
		$this->template->setFile(__DIR__ . '/default.latte');
	}


	/**
	 * @throws AttributeDetectionException
	 */
	public function renderDetail(): void
	{
		$article = $this->article;
		$this->template->article = $article;
		$this->template->category = $article->category_id === null
			? null
			: $this->categoryRepository->get($article->category_id)->record();
		$this->template->content = $this->texyFormatter->toHtml($article->content);
		$this->template->description = $article->perex;
		$this->template->author = $this->articleAuthors->forArticles([$article])[$article->author_id] ?? null;
		$this->template->commentsEnabled = $this->areCommentsEnabled();
		$this->template->commentAuthor = $this->getSignedInAuthor()['name'] ?? null;
		$this->template->comments = $comments = $this->template->commentsEnabled
			? $this->commentRepository->getApprovedByArticle($article->id)->recordAll()
			: [];

		// approved comments are numbered from 1 in their order; "#2" in a comment links the second one
		$names = [];
		foreach ($comments as $i => $comment) {
			$this->template->commentNumbers[$comment->id] = $i + 1;
			$names[$i + 1] = $comment->author_name;
		}

		$references = new CommentReferences($names);
		foreach ($comments as $comment) {
			$html = $this->texyFormatter->toBasicHtml($comment->content);
			$this->template->commentHtml[$comment->id] = $references->link($html, $this->template->commentNumbers[$comment->id]);
		}

		$this->template->commentNames = $names;
		$this->template->commentReplies = $references->getReplies();
	}


	protected function createComponentComment(): Form
	{
		$form = new Forms;
		$form->setTranslator($this->getTranslator());

		// a signed-in user comments under the name of the account
		if ($this->getSignedInAuthor() === null) {
			$form->addTextInput('name', 'Name')
				->setAutocomplete(Autocomplete::Name)
				->setRequired('Please enter your name.')
				->addRule($form::MaxLength, 'The name can have at most %d characters.', 100);

			$form->addEmailInput('email', 'Email (not published)')
				->setAutocomplete(Autocomplete::Email)
				->setNullable()
				->addRule($form::Email, 'Please enter a valid email address.')
				->addRule($form::MaxLength, 'The email can have at most %d characters.', 255);
		}

		$form->addTextAreaInput('content', 'Comment')
			->setHtmlAttribute('data-texy-toolbar', $this->texyToolbar->getBasicOptions($this->link('commentPreview!')))
			->setRequired('Please write your comment.')
			->addRule($form::MaxLength, 'The comment can have at most %d characters.', 2000);

		// hidden from people, a spam bot fills it in
		$form->addTextInput('website', 'Website')
			->setAutocomplete(Autocomplete::Off)
			->setHtmlAttribute('tabindex', '-1');

		$form->addSubmit('send', 'Send comment');
		$form->onSuccess[] = $this->processCommentForm(...);
		return $form;
	}


	/** @param array{name?: string, email?: ?string, content: string, website: string} $values */
	private function processCommentForm(Form $form, array $values): void
	{
		if (!$this->areCommentsEnabled()) {
			$this->error('Comments are not allowed', 403);
		}

		$ip = (string) $this->httpRequest->getRemoteAddress();
		if (!$this->rateLimiter->isAllowed('blog.comment', $ip, self::CommentLimit)) {
			$form->addError('You have sent too many comments, please try again later.');
			return;
		}

		$this->rateLimiter->hit('blog.comment', $ip, self::CommentWindow);
		if ($values['website'] === '') {
			// the name and email of a signed-in user come from the identity, never from the request
			$author = $this->getSignedInAuthor() ?? ['name' => $values['name'] ?? '', 'email' => $values['email'] ?? null];
			$this->commentRepository->addComment($this->article->id, $author['name'], $author['email'], $values['content']);
		}

		// a bot gets the same answer, so it does not learn that the comment was dropped
		$this->flashMessage($this->getTranslator()->translate('Thank you, your comment will appear after approval.'), Alert::Success);
		$this->redirect('this#comments');
	}


	/**
	 * Articles of the list, the categories that have some (for the pills and the cards) and the selected category.
	 * @throws AttributeDetectionException
	 */
	private function setArticleList(?CategoryEntity $category): void
	{
		$categories = $this->categoryRepository->getUsedCategories();
		$this->template->categories = array_column($categories, null, CategoryEntity::PrimaryKey);
		$this->template->category = $category;
		$this->template->articles = $articles = $this->articleRepository
			->getPublishedArticles($category?->id)
			->recordAll();

		$this->template->authors = $this->articleAuthors->forArticles($articles);
		$this->template->commentCounts = $this->commentRepository->getApprovedCounts(array_column($articles, ArticleEntity::PrimaryKey));
	}


	/** Each article allows or disallows its comments (the field "Allow comments" in the administration). */
	private function areCommentsEnabled(): bool
	{
		return (bool) $this->article->comments_enabled;
	}


	/**
	 * Name and email of the signed-in user (identity data of drago-ex/project-auth), who does not fill them in;
	 * null for a guest.
	 * @return array{name: string, email: ?string}|null
	 */
	private function getSignedInAuthor(): ?array
	{
		if (!$this->getUser()->isLoggedIn()) {
			return null;
		}

		$data = $this->getUser()->getIdentity()?->getData() ?? [];
		$name = $data['username'] ?? null;
		if (!is_string($name) || $name === '') {
			return null;
		}

		$email = $data['email'] ?? null;
		return ['name' => $name, 'email' => is_string($email) && $email !== '' ? $email : null];
	}
}
