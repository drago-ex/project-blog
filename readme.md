# Drago Project Blog

Blog articles, categories and comments for Drago Project.

The package adds a backend module for writing articles, managing categories and approving comments, and a
frontend with the article list, categories and article details with comments.

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

The migrations create the tables `blog_article`, `blog_category` and `blog_comment`, and the setting
`blog.comments_enabled` of [drago-ex/project-settings](https://github.com/drago-ex/project-settings).

## Assets

The toolbar of the content field and the copy button of code blocks come from
[drago-ex/texy-toolbar](https://github.com/drago-ex/texy-toolbar); set them up as its README shows (`admin.js` for
the toolbar, `front.js` for the toolbar of comments and the copy button). Without them the blog works too.

## Permissions

The blog works without permissions and without [drago-ex/permission](https://github.com/drago-ex/permission):
the administration shows all its buttons.

For static permissions, the access rules are prepared in [`permission.php`](permission.php); drago-ex/permission
generates a provider from it (set up as its README shows):

```bash
php vendor/bin/create-permission --packages
```

To show the add, edit and delete buttons only to roles with the privilege, add the `WriteAccess` trait of
drago-ex/permission; its `isWriteAllowed()` asks for the `{component}-write` privilege. In
`app/Presentation/Backend/Blog/Component/BaseControl.php` use the trait and pass the result to the template
(with `public bool $writeAllowed = false;` in `BaseTemplate`):

```php
use Drago\Permission\WriteAccess;

abstract class BaseControl extends ExtraControl implements OffcanvasHandle, ModalHandle
{
	use Component;
	use WriteAccess;

	public function createRender(): Template
	{
		// ...
		$template->writeAllowed = $this->isWriteAllowed();
```

then hide the grid actions by it and show the add button only when allowed:

```php
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
	->setVisible($this->isWriteAllowed());
```

```latte
{embed '../@datagrid-heading.latte', addButton: $writeAllowed}
```

For dynamic permissions set in the administration, install
[drago-ex/project-permission](https://github.com/drago-ex/project-permission) and import the privileges of the blog
into its `resources` table by hand, so roles can be given them (e.g. a moderator who only approves comments):

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-blog/migrations-permission
```

In a project composed by [netis](https://github.com/netis-cms/netis), project-preset makes these changes for you.

## Installed Files

The package copies:

- `resources/app/Model` to `app/Model`
- `resources/app/Presentation/Backend` to `app/Presentation/Backend`
- `resources/app/Presentation/Front` to `app/Presentation/Front`

## Backend

The backend presenter is available as:

```latte
{link :Backend:Blog:}
```

With [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui), the item "Blog" with articles,
categories and comments appears in the sidebar under "Content" (`Backend/Blog/6.menu.neon`).

The module uses the permission resource `Backend:Blog`. The backend provider allows the administrator role.

## Frontend

The routes `/blog`, `/blog/kategorie/<slug>` and `/blog/<slug>` (and with `/en/`) are added by
`app/Presentation/Front/Blog/7.conf.neon`, before the front router.

```latte
{link :Front:Blog:}
{link :Front:Blog:detail, slug: 'first-article'}
```

Article content is written in [Texy](https://texy.info/en/syntax) and printed safely by drago-ex/texy-toolbar;
the perex is the meta description of the article.

Anyone may write a comment; it is shown after an administrator approves it. Comments use basic Texy (no images,
headings or HTML) and are allowed by the setting `blog.comments_enabled` together with the article's own switch.

The frontend permission provider uses the resource `Front:Blog` and allows it to guests.
