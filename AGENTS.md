# Agent Guide

`drago-ex/project-blog` (type drago-tools-resource, PHP >=8.3 <9, requires ext-gd and ext-intl): blog articles,
categories and comments, copied into the project by project-tools. The workspace rules are in `../AGENTS.md`;
dependencies are in `composer.json`.

## Layout
- `resources/app/Model/Blog`: entities, repositories, `ArticleImages` (uploads), `ArticleAuthors`,
  `ArticleList` + `ArticleListFactory`.
- `resources/app/Presentation/Backend/Blog`: `BlogPresenter`, components `Article` and `Category`,
  `CommentBadge`, `6.menu.neon`, `acl.neon`, `Translate/cs.neon`.
- `resources/app/Presentation/Front/Blog`: `BlogPresenter`, `BlogRouter` (`7.conf.neon`), `CommentReferences`,
  `TimeAgoExtension` (filter `|ago`), the article templates, `acl.neon`.
- `resources/assets/naja/comment-reply.js`: Naja extension `CommentReply`, copied to `assets/naja/`.
- `migrations/001_blog.sql`, `tests/` (Nette Tester).

## Permissions
- `acl.neon` next to the presenters (resources = presenter names, rules only for roles other than the
  administrator), merged with `role.acl.neon` of drago-ex/permission. A new presenter gets its resource there.
- `BaseControl` uses `AccessCheck`; buttons ask `isAllowed()` (grid actions `setVisible()`, templates
  `$control->isAllowed(...)`), signals are named by `#[Access]`. Privileges of `Backend:Blog`: `default`,
  `categories`, `edit` (article and category forms, row edit, `toggleComments`, main image signals), `delete`,
  `approveComment`, `deleteComment`. `openModal`, `openOffcanvas`, `preview` need only the page.
  `getToggleCommentsLink()` returns null without `edit`.
- project-permission reads the privileges from the code (`PrivilegeCatalog`); a new signal needs its privilege
  decided here, no list to update.

## Administration
- The article form is a full-screen offcanvas with two columns: title and content left (`col-lg-8`), settings in
  cards right (`col-lg-4`): status, date, category, comments / main image / slug, perex. Buttons are in a
  `sticky-bottom` bar. A new field goes into the fitting card; nothing is hidden in tabs or accordions, because
  a field with an error must stay visible. Cards have no headings.
- Main image: `ArticleTemplate::$mainImage` is loaded by `ArticleControl::render()` from the form's id, so it stays
  after a validation error. The trash button on the preview removes it by signals `confirmRemoveImage`,
  `cancelRemoveImage`, `removeImage`, which redraw only the snippet `mainImage`, so unsaved form changes stay.
- `ArticleImages` stores uploads in `www/upload/blog/` (fit 1600 × 1200, random name) and deletes them on replace,
  remove and article delete. `ArticleValues::$image` and `$authorId` have no default: uninitialized, they are
  not saved, so an edit keeps them.
- Categories are written in the article form: the "+" button switches the select to the field `newCategory`,
  "×" empties it and goes back; a filled name wins over the select (`ArticleTemplate::$newCategory` keeps it open
  after an error). `CategoryRepository::getOrCreate()` reuses a category of the same name or creates one with a
  `Strings::webalize()` slug, numbered when taken. The categories page is reached by the button "Categories" in
  the heading of the articles (`{plink}` in `{block buttons}`), not by the menu.
- Comments are managed with their article only (no comments page): the grid column "Comments" shows the state
  badge (a click toggles it), the count and the pending count (`comments_total`, `comments_pending` subqueries
  of `getArticlesFluent()`); the edit offcanvas has the tabs "Article | Comments (n)" outside the form. Approving
  and deleting are signals of `ArticleControl` that redraw only the snippets `comments` and `commentCount`.
- `CommentBadge` (in `6.menu.neon`, tag `sidebar.badge`, `autowired: no`) returns the pending count for the menu
  badge and the navbar bell of project-backend-ui; it is duck-typed, so the blog needs no backend-ui class.

## Website
- What the front shows is only `ArticleRepository::publishedCondition()` (published and dated), used by the list,
  the detail and `CategoryRepository::getUsedCategories()`.
- Every article list (blog, category, the home page of project-front-ui) is
  `ArticleListFactory::create(?categoryId, ?limit)` rendered by `@article-list.latte`: the newest article as
  `@article-featured.latte`, the rest as cards `@article-item.latte`. The card styles (`.blog-card`,
  `.blog-pill`) are in `front.scss` of project-front-ui.
- `ArticleAuthors` gives names and Gravatar URLs, so email addresses never reach templates.
- Comments: allowed per article (`blog_article.comments_enabled`), no global setting. Approved comments are
  numbered (`#comment-N`); `CommentReferences` links "#N" in the Texy HTML text and collects replies
  (test `CommentReferences.phpt`). A signed-in user comments with the `username`/`email` of the identity, taken on
  submit, never from the request. "Reply" (`data-comment-reply`) needs `CommentReply`; the form box has
  `id="comment-form"`.

## Commands
```bash
composer phpstan
composer tester     # tests/bootstrap.php loads App\ from resources/app, then .dev-resource/app
```
