# Drago Project Blog

Blog articles and comments for Drago Project.

The package adds a backend module for writing articles and database support for article comments. Comments are controlled by the global setting `blog.comments_enabled` and by the article field `comments_enabled`.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-blog.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-blog)
[![Coding Style](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- Composer

## Installation

```bash
composer require drago-ex/project-blog
```

## Project files

File copying is handled automatically by [drago-ex/project-tools](https://github.com/drago-ex/project-tools),
which must be installed in your project. Without it, copy the files manually according to the `copy` section
in this package's `composer.json`. To skip this package, set `"skip": true` under
`extra.drago-tools.packages.<package-name>` in your root `composer.json`.

## Database migration

```bash
php vendor/bin/migration db:migrate --packages
```

The migration runs after the one of drago-ex/project-settings (`migration-priority` 110) and creates:

- `blog_article`
- `blog_comment`
- settings value `blog.comments_enabled`

## Permission provider

With [drago-ex/permission](https://github.com/drago-ex/permission), the access rules from [`permission.php`](permission.php)
are generated into the project by:

```bash
php vendor/bin/create-permission --packages
```

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

The frontend permission provider uses the resource `Front:Blog` and allows it to guests, so the article listing and detail are available without login.

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
