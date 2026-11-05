# Drago Project Blog

Blog for Drago Project: articles with categories, cover images and comments. The administration writes
articles and approves comments, the website shows the article list, categories and article details with comments.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-blog.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-blog)
[![Coding Style](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- PHP extensions GD and intl
- [Drago Project](https://github.com/drago-ex/project)

## Installation

```bash
composer require drago-ex/project-blog
```

[Project Tools](https://github.com/drago-ex/project-tools) copies the files of the package into the project,
where you adapt them.

## Database

The migrations create the tables `blog_article`, `blog_category` and `blog_comment`; run them with
`bin/project-setup` of Drago Project or `php vendor/bin/migration db:migrate --packages`.

## Assets

### Comment replies

Register the Naja extension that puts "#2 Name" into the comment form after a click on "Reply", in
`assets/front.js` (without it the link only goes to the form):

```js
import CommentReply from './naja/comment-reply.js';

registerNajaExtensions(
	// ...
	CommentReply
);
```

### Used libraries

- [drago-ex/datagrid](https://github.com/drago-ex/datagrid): the lists of articles and categories, set up in
  `admin.js` and `admin.scss`.
- [drago-ex/texy-toolbar](https://github.com/drago-ex/texy-toolbar): the toolbar of the content field (`admin.js`),
  of comments and the copy button of code blocks (`front.js`).

## Administration

```latte
{link :Backend:Blog:}            {* articles with their comments *}
{link :Backend:Blog:categories}  {* categories *}
```

The menu item comes from `Backend/Blog/6.menu.neon` with [Backend UI](https://github.com/drago-ex/project-backend-ui),
together with the number of comments waiting for approval (`CommentBadge`), shown in the menu and the navbar bell.

- A new category is written right in the article form (the "+" button next to the category); its slug is made
  from the name. The button "Categories" above the articles opens their list for renaming and deleting.
- The cover image is resized to fit 1600 × 1200 px and saved in `www/upload/blog/`; replacing or removing it and
  deleting the article delete the file. Add `/www/upload/` to `.gitignore`.
- The article list shows whether comments are allowed (a click switches it), their number and those waiting for
  approval; an opened article has the tab "Comments" to approve or delete them.

## Website

The routes `/blog`, `/blog/kategorie/<slug>` and `/blog/<slug>` (also with `/en/`) are added by
`Front/Blog/7.conf.neon`, before the front router.

```latte
{link :Front:Blog:}
{link :Front:Blog:detail, slug: 'first-article'}
```

- Articles are written in [Texy](https://texy.info/en/syntax); the perex is the meta description.
- The newest article is shown over the whole row (`@article-featured.latte`), the others as cards
  (`@article-item.latte`) with the image, date, category, perex and the author with a Gravatar. Other pages show
  articles the same way with `ArticleListFactory::create()` and `@article-list.latte`.
- Anyone may comment; a comment is shown after approval. Each article allows comments by its own switch.
  A signed-in user comments under the name and email of the account. Comments are numbered, and "#2" in a comment
  links that comment, which lists its replies.

## Permissions

The rules are in `Backend/Blog/acl.neon` (resource `Backend:Blog`, the administrator only) and
`Front/Blog/acl.neon` (resource `Front:Blog`, everyone). The privileges of `Backend:Blog` are `default`,
`categories`, `edit`, `delete`, `approveComment` and `deleteComment`; buttons are shown only to those who may use
them. [Project Permission](https://github.com/drago-ex/project-permission) gives them to other roles, e.g. a
moderator with `default` and `approveComment`.

The same files describe the privileges (`permissionLabels`) for the permissions page of [Project Permission](https://github.com/drago-ex/project-permission).
