# Drago Project Blog

Blog articles and comments for Drago Project.

The package adds a backend module for writing articles and database support for article comments. Comments are controlled by the global setting `blog.comments_enabled` and by the article field `comments_enabled`.

## Installation

```bash
composer require drago-ex/project-blog
```

Run project setup after installation:

```bash
php vendor/bin/drago-setup
```

The setup command can run the package migration:

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-blog/migrations
```

The migration creates:

- `blog_article`
- `blog_comment`
- settings value `blog.comments_enabled`

## Installed Files

The package copies:

- `resources/app/Model/Blog` to `app/Model/Blog`
- `resources/app/Presentation/Backend/Blog` to `app/Presentation/Backend/Blog`

## Backend

The backend presenter is available as:

```latte
{link :Backend:Blog:}
```

If you use the bundled backend sidebar, add the menu item in your backend presenter:

```php
$builder->addSection('Content')
	->addItem('Blog', 'Blog:')
	->setIcon('fa-regular fa-newspaper');
```

The module uses the permission resource `Backend:Blog`. Write actions are checked with the `blog-write` privilege.

## Frontend List

Use `ArticleRepository` to list published articles:

```php
use App\Model\Blog\ArticleRepository;

final class BlogPresenter extends BasePresenter
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
	) {
		parent::__construct();
	}

	public function renderDefault(): void
	{
		$this->template->articles = $this->articleRepository
			->getPublishedArticles()
			->fetchAll();
	}
}
```

## Frontend Detail

Load an article by slug and show approved comments:

```php
use App\Model\Blog\ArticleRepository;
use App\Model\Blog\CommentRepository;

final class BlogPresenter extends BasePresenter
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
		private readonly CommentRepository $commentRepository,
	) {
		parent::__construct();
	}

	public function renderDetail(string $slug): void
	{
		$article = $this->articleRepository->getPublishedBySlug($slug);
		if ($article === null) {
			$this->error();
		}

		$this->template->article = $article;
		$this->template->comments = $this->commentRepository
			->getApprovedByArticle($article->id)
			->fetchAll();
	}
}
```

Template example:

```latte
{block content}
	<h1>{$article->title}</h1>
	<div n:if="$article->perex">{$article->perex}</div>
	<div>{$article->content|noescape}</div>

	<section n:if="$article->comments_enabled">
		<h2>{_'Comments'}</h2>
		{foreach $comments as $comment}
			<article>
				<strong>{$comment->author_name}</strong>
				<p>{$comment->content}</p>
			</article>
		{/foreach}
	</section>
{/block}
```

Use `|noescape` only when the article content is trusted HTML edited by an administrator.

## Comments

The package stores comments in `blog_comment`. New comments should be inserted as unapproved by default and displayed only after approval:

```php
$commentRepository->addComment([
	'article_id' => $article->id,
	'author_name' => $values->name,
	'author_email' => $values->email,
	'content' => $values->content,
	'approved' => 0,
]);
```

Check both comment switches before accepting a comment:

```php
$commentsEnabled = $settings->get('blog.comments_enabled') === '1'
	&& (bool) $article->comments_enabled;
```
