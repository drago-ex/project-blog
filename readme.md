# Drago Project Blog

Blog articles, categories and comments for Drago Project.

The package adds a backend module for writing articles, managing categories and approving comments with their article, and a
frontend with the article list, categories and article details with comments.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-blog.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-blog)
[![Coding Style](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- PHP extensions GD (resizing the cover images) and intl (slugs of new categories); the Docker image of
  [drago-ex/project-docker](https://github.com/drago-ex/project-docker) has both
- [Drago Project](https://github.com/drago-ex/project)

## Installation

```bash
composer require drago-ex/project-blog
```

## Files copied into the project

The files are copied into the project by [drago-ex/project-tools](https://github.com/drago-ex/project-tools),
as set in `extra.drago-tools` of this package's `composer.json`.

## Database migration

```bash
php vendor/bin/migration db:migrate --packages
```

The migrations create the tables `blog_article`, `blog_category` and `blog_comment`.

## Assets

Register the Naja extension, which puts "#2 Name" into the comment form after a click on "Reply" under a comment, in
`assets/front.js` (without it the link only goes to the form):

```js
import CommentReply from './naja/comment-reply.js';

registerNajaExtensions(
	// ...
	CommentReply
);
```

The toolbar of the content field and the copy button of code blocks come from
[drago-ex/texy-toolbar](https://github.com/drago-ex/texy-toolbar); set them up as its README shows (`admin.js` for
the toolbar, `front.js` for the toolbar of comments and the copy button). Without them the blog works too.

The lists of articles and categories come from [drago-ex/datagrid](https://github.com/drago-ex/datagrid); set up its assets in
`admin.js` and `admin.scss` as its README shows.

## Backend

The backend presenter is available as:

```latte
{link :Backend:Blog:}            {* articles with their comments *}
{link :Backend:Blog:categories}  {* categories *}
```

With [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui), the page gets its menu item from `Backend/Blog/6.menu.neon`.

A new category is written right in
the article form (the "+" button next to the category); the button "Categories" above the articles opens their list for renaming and
deleting. The slug of a new category is made from its name. Comments are managed with their
article: the article list shows whether they are allowed (a click on the state switches it), their number and those
waiting for approval, and an opened article has the tab "Comments" to approve or delete them. The number of all
comments waiting for approval comes from `CommentBadge`, a service for the menu numbers and the navbar bell of
project-backend-ui, registered in `Backend/Blog/6.menu.neon`.

The module uses the permission resource `Backend:Blog`.

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
`www/upload/blog/`; replacing or removing the image and deleting the article delete the file.
Add `/www/upload/` to `.gitignore`.

Anyone may write a comment; it is shown after an administrator approves it. Comments use basic Texy (no images,
headings or HTML). Each article allows or disallows them by its own "Allow comments" switch. A signed-in user does
not fill in the name and email, the comment gets those of the account. Approved comments are numbered (`#1`, `#2`,
…) and "Reply" puts `#2 Name` into the form; such a number in a comment links the comment, which lists its replies.
Putting the text into the form needs the Naja extension `CommentReply` (see Assets).

The resource `Front:Blog` is allowed to guests.

## Access rules

The access rules are in `acl.neon` next to the presenters: `app/Presentation/Backend/Blog/acl.neon` (the resource `Backend:Blog`, for the administrator only)
and `app/Presentation/Front/Blog/acl.neon` (the resource `Front:Blog` for everyone).
[drago-ex/permission](https://github.com/drago-ex/permission) merges them with its base roles, in which the administrator is allowed everything.

The privileges of the administration (`Backend:Blog`) are `default` and `categories` (the pages), `edit` (adding
and editing articles and categories, the comments switch, the main image), `delete`, `approveComment` and
`deleteComment`. Its buttons are shown only to those who may use them. With
[drago-ex/project-permission](https://github.com/drago-ex/project-permission), roles can be given these privileges
in its administration, e.g. a moderator with `default` and `approveComment`.
