# Drago Project Blog

English | [Čeština](readme.cs.md)

Blog articles, categories and comments for Drago Project.

The package adds a backend module for writing articles, managing categories and approving comments with their article, and a
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

The migrations create the tables `blog_article`, `blog_category` and `blog_comment`.

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

In the article, show the approve and delete buttons of the comments tab (`<div class="article-comment-actions …">`)
only with `n:if="$writeAllowed"`, and let `ArticleControl::getToggleCommentsLink()` return null without the
privilege, so the state of the comments in the grid is not clickable.

For dynamic permissions set in the administration, install
[drago-ex/project-permission](https://github.com/drago-ex/project-permission) and import the privileges of the blog
into its `resources` table by hand, so roles can be given them (e.g. an editor who only views articles):

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-blog/migrations-permission
```

In a project composed by [netis](https://github.com/netis-cms/netis), project-preset makes these changes for you
and the web installer of project-install imports the privileges.

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

With [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui), the item "Blog" appears in the sidebar
under "Content" (`Backend/Blog/6.menu.neon`). A new category is written right in
the article form ("+ New category"); the button "Categories" above the articles opens their list for renaming and
deleting. The slug of a new category is made from its name with the PHP extension intl. Comments are managed with their
article: the article list shows whether they are allowed (a click on the state switches it), their number and those
waiting for approval, and an opened article has the tab "Comments" to approve or delete them. The number of all
comments waiting for approval is shown next to "Blog" in the menu and in the bell of the navbar (`CommentBadge`).

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

The list shows the articles as cards side by side: a wide image, a relative date (`{$date|ago}`, e.g. "2 months
ago") with the category, the title, the perex and the author with a [Gravatar](https://gravatar.com) avatar
(drago-ex/gravatar, registered in `Front/Blog/7.conf.neon`). The newest article is shown over the whole row with a
large image beside the text (`Front/Blog/@article-featured.latte`), the others are cards
(`Front/Blog/@article-item.latte`). Other pages show articles the same way with `ArticleListFactory::create()` and
`Front/Blog/@article-list.latte`. The author is the signed-in user who created the article.

The cover image is uploaded in the article form. It is made smaller to fit 1600 × 1200 px and saved in
`www/upload/blog/`; replacing or removing the image and deleting the article delete the file. Resizing needs the
PHP extension GD (the Docker image of drago-ex/project-docker has it); without it the form offers no upload.
Add `/www/upload/` to `.gitignore`.

Anyone may write a comment; it is shown after an administrator approves it. Comments use basic Texy (no images,
headings or HTML). Each article allows or disallows them by its own "Allow comments" switch. A signed-in user does
not fill in the name and email, the comment gets those of the account. Approved comments are numbered (`#1`, `#2`,
…) and "Reply" puts `#2 Name` into the form; such a number in a comment links the comment, which lists its replies.
The reply link needs `front.js` of drago-ex/project-preset (without it the link only goes to the form).

The frontend permission provider uses the resource `Front:Blog` and allows it to guests.
