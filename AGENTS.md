# Agent Guide

`drago-ex/project-blog` (type drago-tools-resource, PHP >=8.3 <9, requires ext-gd and ext-intl): blog articles,
categories and comments, copied into the project by project-tools. The workspace rules are in `../AGENTS.md`;
dependencies are in `composer.json`.

## Layout
- `resources/app/Model/Blog`: entities, repositories, `ArticleImages` (uploads), `ArticleAuthors`,
  `ArticleList` + `ArticleListFactory`.
- `resources/app/Presentation/Backend/Blog`: `BlogPresenter`, components `Article` and `Category`,
  `CommentBadge`, `DashboardWidget`, `6.menu.neon`, `acl.neon`, `Translate/cs.neon`.
- `resources/app/Presentation/Front/Blog`: `BlogPresenter`, `BlogRouter` (`7.conf.neon`), `CommentReferences`,
  `TimeAgoExtension` (filter `|ago`), the article templates, `acl.neon`.
- `resources/assets/naja/comment-reply.js`: Naja extension `CommentReply`, copied to `assets/naja/`.
- `package.json` (with `name`): `compostrap` and `vanilla-calendar-pro` (the datepicker of the date of publication), installed
  by `npm-loader.js` of drago-ex/project; compostrap keeps the calendar an optional peer, so the user of the datepicker
  declares it.
- `migrations/001_blog.sql`, `tests/` (Nette Tester).

## Permissions
- `acl.neon` next to the presenters (resources = presenter names, rules only for roles other than the
  administrator), merged with `role.acl.neon` of drago-ex/permission. A new presenter gets its resource there.
- `BaseControl` uses `AccessCheck`; buttons ask `isAllowed()` (grid actions `setVisible()`, templates
  `$control->isAllowed(...)`), signals are named by `#[Access]`. Privileges of `Backend:Blog`: `default`,
  `edit` (article and category forms, the Edit of the row menu, `add` of the categories, main image signals), `delete` (also
  `confirmDelete`/`cancelDelete`/`remove` of the categories), `publish` (the menu of the status in the article grid, both ways),
  `export` (CSV of the articles), `approveComment`, `deleteComment`. `openModal`, `openOffcanvas`, `preview`, `comments` (opening
  the comments of an article) and `open` of the categories need only the page.
- project-permission reads the privileges from the code (`PrivilegeCatalog`); a new signal needs its privilege
  decided here, no list to update.

## Administration
- The article form is a full-screen offcanvas with two columns: title and content left (`col-lg-8`), settings in
  cards right (`col-lg-4`): status, date, category, comments / main image / slug, perex. Buttons are in a
  `offcanvas-actions` bar of compostrap (the cards are `card border-0 bg-body-tertiary`, without a shadow). A new field goes into the fitting card; nothing is hidden in tabs or accordions, because
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
  `Strings::webalize()` slug, numbered when taken. There is no categories page: the button "Categories" in the heading of the
  articles opens `CategoryControl`, the child `categories` of `ArticleControl`, in its offcanvas (`categories-open!`). The list
  (name and a menu of three dots with Edit and Delete as the row menu of compostrap, without
  `strategy: fixed`, which misplaces the menu inside the transformed offcanvas; "Add category") and the form of a category (title, slug) take turns in it (`$showForm`);
  Cancel of the form goes back to the list, a delete is confirmed in its row like a comment (`confirmDelete!`, `remove!`), and
  the list is loaded only after a signal opened it. `onChange` redraws the article grid, which shows the names.
- Every grid has inline filters and no row click that opens the form: a row has a menu of three dots (row actions of drago-ex/datagrid, `addActionEdit()` and `addActionDelete()`, the latter calls `handleDelete([$id])`), the bar under the table has the bulk actions (articles: Export, Delete), a click anywhere on a row chooses it (`setRowClickAction('select')`), states are badges that are only read (`setBadges()`). The name of a row or bulk action is its privilege, and the control has a `handle<Name>()` method the callback calls, so `PrivilegeCatalog` finds the privilege; `export` comes from `DataGrid::handleExport()`. `BaseControl::handleDelete(array $ids)` opens one confirmation for all chosen items (`$deleteTitles`, the hidden field `ids` of the delete form) and `deleteItem()` runs for each. The state (published or draft, `handlePublish(int $id, bool $publish)`) is changed in its column by the menu of its badge (`setState()` of drago-ex/datagrid), not by a switch or a bulk action (user's choice: many switches in a table are noise). The column "Published at" has a date range filter. `ArticleRepository::setStatus()` gives a published article without a date the current one, or the front would not show it.
- Article grid: Title (plain text, no thumbnail of the main image), Category (the name as plain
  text, no colored badge), Comments, Published at (date and time, date range filter) and Status last. The grid has no Slug and no
  Author column, so it stays short.
- Comments are managed with their article only (no comments page): the grid column "Comments" (`comments_total`, `comments_pending` subqueries
  of `getArticlesFluent()`) is a link styled as a subtle badge (`a.badge`, the height of the state badge, so the row is not higher than the rows of the other grids) with a chat icon and the number of comments (`formatComments()`, gray without comments; its tooltip and `aria-label` say what it
  does, "Show the comments", not the number, which is visible) and a badge of the ones
  waiting for approval. The button calls `handleComments(int $id)` (`#[Access]`, it only reads), which opens the second offcanvas of the control
  (`$commentsId`, the id sent in `payload->offcanvas`) with the snippets `commentsTitle` and `comments`; the article form has no tab of comments.
  Approving and deleting are signals of `ArticleControl` that redraw that offcanvas and the grid. Whether an article allows comments is set
  only in its form (`commentsEnabled`).
- The date of publication is the datepicker of compostrap with a time (`data-bs-selection-time-mode="24"`), printed in
  `Article.latte` as a text field with the value `Y-m-d H:i` (Nette prints `datetime-local` with a `T`); `addDateTime()` reads it.
- The main image of the article form is a dropzone of compostrap (`label.dropzone` with the `upload` input as `dropzone-input`): the file input
  covers the area, so it stays the field of the Nette form and its rules. It has no `data-dropzone-submit`, the image is saved with the article
  (an automatic submit would send the whole unfinished article). `DropzoneInput.init()` runs in `admin.js` on load and after every snippet update,
  because the form is redrawn in the offcanvas. A validation message shows under the area (`form-error`).
- `CommentBadge` (in `6.menu.neon`, tag `sidebar.badge`, `autowired: no`) returns the pending count for the menu
  badge and the navbar bell of project-backend-ui; it is duck-typed, so the blog needs no backend-ui class.

## Website
- `DashboardWidget` (in `6.menu.neon`, tag `dashboard.widget`, `autowired: no`) returns the widgets of the dashboard of
  project-backend-ui as arrays (duck-typed like `CommentBadge`): a column chart of all comments per day of the last 30
  days (`CommentRepository::countByDay()` fills the days without one) and a ranking of the 5 articles with the most
  approved comments (`getMostCommented()`). Titles are translation keys of `Translate/cs.neon`, article titles are
  data. Two aggregated queries.
- What the front shows is only `ArticleRepository::publishedCondition()` (published and dated), used by the list,
  the detail and `CategoryRepository::getUsedCategories()`.
- Every article list (blog, category, the home page of project-front-ui) is
  `ArticleListFactory::create(?categoryId, ?limit)` rendered by `@article-list.latte`: the newest article as
  `@article-featured.latte`, the rest as cards `@article-item.latte`. The front templates are written in
  the classes of the compostrap module `blog` (`blog-title`, `blog-pills`, `blog-featured`, `blog-card`, `blog-meta`,
  `blog-author` with the Gravatar in an `avatar`, `blog-article`, `blog-content`, `blog-comment`, `blog-comment-form`),
  which `front.scss` of project-front-ui imports; without project-front-ui the project imports it itself (README Assets › Website), else the fixed-size images overflow the page. The avatar of a comment holds the initials of its author in a
  `theme-*` color picked by `crc32()` of the name.
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
