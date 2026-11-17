# Drago Project Blog

Blog for Drago Project: articles with categories, cover images and comments. The administration writes
articles and approves comments, the website shows the article list, categories and article details with comments.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-blog.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-blog)
[![Coding Style](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml)

## Requirements

- PHP >= 8.3, < 9
- PHP extensions GD and intl
- MySQL or MariaDB
- [Drago Project](https://github.com/drago-ex/project)

## Installation

```bash
composer require drago-ex/project-blog
```

[Project Tools](https://github.com/drago-ex/project-tools) copies the files of the package into the project,
where you adapt them. Add its scripts and styles as [Assets](#assets) shows, run `npm install` (the package brings
`vanilla-calendar-pro`) and build the assets again (`npm run vite:build`).

## Database

The migrations create the tables `blog_article`, `blog_category` and `blog_comment`, empty:

```bash
php vendor/bin/migration db:migrate --packages
```

Articles and categories are then written in the administration. `bin/project-setup` of Drago Project runs the
migrations for you.

## Assets

### Website

The pages of the blog on the website (the list, the article, the comments) are in the look of the module `blog` of
[compostrap](https://github.com/compostrap/compostrap); without it the images of the articles overflow the page.
[Front UI](https://github.com/drago-ex/project-front-ui) already imports it; otherwise import it in `assets/front.scss`:

```scss
@import 'compostrap/src/scss/blog';
```

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

### Main image of an article

The main image is dropped on or chosen in a dropzone of [compostrap](https://github.com/compostrap/compostrap).
Bind it in `assets/admin.js`, also after a redraw of a snippet (the form opens in an offcanvas):

```js
import { DropzoneInput } from 'compostrap/dropzone';

DropzoneInput.init();
naja.snippetHandler.addEventListener('afterUpdate', (event) => DropzoneInput.init(event.detail.snippet));
```

and import its styles in `assets/admin.scss`:

```scss
@import 'compostrap/src/scss/dropzone';
```

### Date of publication

The date of publication of an article is a datepicker of [compostrap](https://github.com/compostrap/compostrap) with a
time. The package brings `vanilla-calendar-pro` in its `package.json`; it needs `Datepicker.init()` in
`assets/admin.js` and its styles in `assets/admin.scss` (the same lines as the date filter of
[drago-ex/datagrid](https://github.com/drago-ex/datagrid)):

```js
import { Datepicker } from 'compostrap/datepicker';

Datepicker.init();
```

```scss
@import 'compostrap/src/scss/datepicker';
```

### Used libraries

- [drago-ex/datagrid](https://github.com/drago-ex/datagrid): the list of articles, set up in
  `admin.js` and `admin.scss`.
- [drago-ex/texy-toolbar](https://github.com/drago-ex/texy-toolbar): the toolbar of the content field (`admin.js`),
  of comments and the copy button of code blocks (`front.js`).

## Administration

```latte
{link :Backend:Blog:}  {* articles with their comments and categories *}
```

The menu item comes from `Backend/Blog/6.menu.neon` with [Backend UI](https://github.com/drago-ex/project-backend-ui),
together with the number of comments waiting for approval (`CommentBadge`), shown in the menu and the navbar bell.
The dashboard of Backend UI shows the widgets of the blog (`DashboardWidget`): the comments of the last 30 days as
a chart and the most commented articles.

- A new category is written right in the article form (the "+" button next to the category); its slug is made
  from the name. The button "Categories" above the articles opens them in a side panel to add, rename
  and delete them; the articles of a deleted category stay without one.
- The cover image is resized to fit 1600 × 1200 px and saved in `www/upload/blog/`; replacing or removing it and
  deleting the article delete the file. Add `/www/upload/` to `.gitignore`.
- The article list shows the main image, the category and the date of publication of each article.
- The status of an article is a badge in the article list; a click on it opens a menu that publishes the article
  or takes it back to a draft.
- The article list shows the number of comments of each article and those waiting for approval; a click on the
  number opens the comments in a side panel to approve or delete them.

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
- Anyone may comment; a comment is shown after approval. Each article allows comments by a switch in its form.
  A signed-in user comments under the name and email of the account. Comments are numbered, and "#2" in a comment
  links that comment, which lists its replies.

## Permissions

The rules are in `Backend/Blog/acl.neon` (resource `Backend:Blog`, the administrator only) and
`Front/Blog/acl.neon` (resource `Front:Blog`, everyone). The privileges of `Backend:Blog` are `default`,
`edit`, `delete`, `publish`, `export`, `approveComment` and `deleteComment`; buttons are shown only to those who may use
them. [Project Permission](https://github.com/drago-ex/project-permission) gives them to other roles, e.g. a
moderator with `default` and `approveComment`.

The same files describe the privileges (`permissionLabels`) for the permissions page of [Project Permission](https://github.com/drago-ex/project-permission).
