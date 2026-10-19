<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use App\Core\Settings\SettingsRepository;
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
		private readonly CategoryRepository $categoryRepository,
		private readonly CommentRepository $commentRepository,
		private readonly SettingsRepository $siteSettings,
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
		$this->template->commentsEnabled = $this->areCommentsEnabled();
		$this->template->comments = $comments = $this->template->commentsEnabled
			? $this->commentRepository->getApprovedByArticle($article->id)->recordAll()
			: [];

		foreach ($comments as $comment) {
			$this->template->commentHtml[$comment->id] = $this->texyFormatter->toBasicHtml($comment->content);
		}
	}


	protected function createComponentComment(): Form
	{
		$form = new Forms;
		$form->setTranslator($this->getTranslator());
		$form->addTextInput('name', 'Name')
			->setAutocomplete(Autocomplete::Name)
			->setRequired('Please enter your name.')
			->addRule($form::MaxLength, 'The name can have at most %d characters.', 100);

		$form->addEmailInput('email', 'Email (not published)')
			->setAutocomplete(Autocomplete::Email)
			->setNullable()
			->addRule($form::Email, 'Please enter a valid email address.')
			->addRule($form::MaxLength, 'The email can have at most %d characters.', 255);

		$form->addTextAreaForm('content', 'Comment')
			->setHtmlAttribute('data-texy-toolbar', $this->texyToolbar->getBasicOptions($this->link('commentPreview!')))
			->setRequired('Please write your comment.')
			->addRule($form::MaxLength, 'The comment can have at most %d characters.', 2000);

		// hidden from people, a spam bot fills it in
		$form->addTextInput('website', 'Website')
			->setAutocomplete(Autocomplete::Off)
			->setHtmlAttribute('tabindex', '-1');

		$form->addSubmit('send', 'Send comment');
		$form->setDefaults($this->getAuthorDefaults());
		$form->onSuccess[] = $this->processCommentForm(...);
		return $form;
	}


	/** @param array{name: string, email: ?string, content: string, website: string} $values */
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
			$this->commentRepository->addComment($this->article->id, $values['name'], $values['email'], $values['content']);
		}

		// a bot gets the same answer, so it does not learn that the comment was dropped
		$this->flashMessage($this->getTranslator()->translate('Thank you, your comment will appear after approval.'), Alert::Success);
		$this->redirect('this#comments');
	}


	/**
	 * Articles of the list, the categories that have some (for the filter) and the selected category.
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

		$this->template->commentsAllowed = $this->areCommentsAllowed();
		$this->template->commentCounts = $this->template->commentsAllowed
			? $this->commentRepository->getApprovedCounts(array_column($articles, ArticleEntity::PrimaryKey))
			: [];
	}


	/** Both switches: the setting blog.comments_enabled and the article field. */
	private function areCommentsEnabled(): bool
	{
		return $this->areCommentsAllowed() && (bool) $this->article->comments_enabled;
	}


	/** The setting blog.comments_enabled (drago-ex/project-settings) allows comments on the whole blog. */
	private function areCommentsAllowed(): bool
	{
		$settings = $this->siteSettings->getSettings();
		return ($settings['blog.comments_enabled'] ?? '0') === '1';
	}


	/**
	 * A signed-in user does not have to fill in the name and email (identity data of drago-ex/project-auth).
	 * @return array<string, string>
	 */
	private function getAuthorDefaults(): array
	{
		$data = $this->getUser()->getIdentity()?->getData() ?? [];
		return array_filter([
			'name' => is_string($data['username'] ?? null) ? $data['username'] : '',
			'email' => is_string($data['email'] ?? null) ? $data['email'] : '',
		]);
	}
}
