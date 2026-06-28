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

It can also generate permission providers:

```bash
php vendor/bin/create-blog-permission
```

The migration creates:

- `blog_article`
- `blog_comment`
- settings value `blog.comments_enabled`

## Installed Files

The package copies:

- `resources/app/Model/Blog` to `app/Model/Blog`
- `resources/app/Presentation/Backend/Blog` to `app/Presentation/Backend/Blog`
- `resources/app/Presentation/Front/Blog` to `app/Presentation/Front/Blog`

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

The module uses the permission resource `Backend:Blog`. Write actions are checked with the `blog-write` privilege. The backend provider allows the administrator role.

## Frontend

The package includes a simple frontend presenter for listing published articles and rendering a detail by slug:

```latte
{link :Front:Blog:}
{link :Front:Blog:detail, slug: 'first-article'}
```

Add routes to your frontend router if you want clean URLs:

```php
$router->withModule('Front')
	->addRoute('[<lang=cs cs|en>/]blog/<slug>', 'Blog:detail')
	->addRoute('[<lang=cs cs|en>/]blog', 'Blog:default');
```

Use `ArticleRepository` directly when you need a custom frontend:

```php
$articles = $this->articleRepository->getPublishedArticles()->recordAll();
$article = $this->articleRepository->getPublishedBySlug($slug);
```

Use `CommentRepository` to show approved comments:

```php
$comments = $this->commentRepository
	->getApprovedByArticle($article->id)
	->recordAll();
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

The frontend permission provider uses the resource `Front:Blog`. The guest role can use `blog-read` and `blog-view`. The `blog-comment` privilege is allowed only for the registered user role.

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
