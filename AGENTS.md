# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/project-blog`
- **Type**: drago-tools-resource
- **Purpose**: Blog articles and comments for Drago Project.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: nette/application (^3.2), nette/di (^3.2), drago-ex/application (^3.0 || ^4.0), drago-ex/component (^2.0), drago-ex/database (^3.0 || ^4.0), drago-ex/datagrid (^2.0), drago-ex/form (^3.0), drago-ex/migration (^2.3), drago-ex/project-auth (^2.0), drago-ex/project-backend (^2.0), drago-ex/translator (^4.0), drago-ex/utils (^2.0), drago-ex/texy-toolbar (^1.0), drago-ex/gravatar (^2.2)
- **License**: MIT

## Repository Layout
- `bin/`: command-line entry points.
- `migrations/`: database migrations.
- `resources/`: application resources contributed to the consuming Drago project.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Buttons and permissions
- The package has no permission code (no write check, no `setVisible()`, no `$writeAllowed`): all add/edit/delete buttons are shown. The README "Permissions" section lists the optional manual steps: static permissions (permission.php, create-permission; buttons by `use Drago\Permission\WriteAccess` in `BaseControl`, `$writeAllowed` in the template, `->setVisible($this->isWriteAllowed())` on grid actions, `addButton: $writeAllowed`) and dynamic ones with project-permission (import `migrations-permission/`). project-preset is meant to ship those changes pre-done. Do not add permission code to the package.
- `@datagrid-heading.latte` shows the add button with `addButton: true` (the comment grid has none).
- The add/edit form in the full-screen offcanvas has two columns (2026-10-24, the user's choice over tabs or an accordion, which would hide fields with errors): the title and the content on the left (`col-lg-8`), the settings in cards on the right (`col-lg-4`), and the buttons in a `sticky-bottom` bar, so they stay visible while the content scrolls. A new field goes into the fitting card, nothing is hidden.
- Article form cards: first card: status, date, category, comments; second: the main image (preview of the stored image from `ArticleTemplate::$mainImage`, which `ArticleControl::render()` loads by the form's id, so it stays after a validation error; upload; "Remove the main image" only when there is one); third: slug, perex. The cards have no headings, the field labels are enough (the user removed them).
- Articles have a cover image (`image`, a file name) and an author (`author_id` → `users`, ON DELETE SET NULL). `Model/Blog/ArticleImages` stores uploads in `www/upload/blog/` (resized to fit 1600 × 1200 with Nette Image, random name, deleted on replace, remove and article delete); it needs ext-gd, which is only suggested in composer.json, so hosts without GD can still install. `ArticleValues::$image` and `$author_id` are declared without a default: uninitialized, they are not saved (`save()` casts the values to an array), so an edit keeps them. `Model/Blog/ArticleAuthors` gives the name and the Gravatar URL, so email addresses never reach templates. `Front/Blog/@article-item.latte` is the card of the blog list and of the project-preset home page, like blog.getbootstrap.com (2026-10-24, the user's choice): cards side by side (2/3 columns), a soft background (`.blog-card` in project-preset front.scss) and no border or shadow, a smaller wide image (2:1, `--bs-aspect-ratio: 50%` because Bootstrap has no ratio-2x1), date · category, title (stretched link over the whole card; `.blog-card-over` keeps the category and comment links clickable), perex clamped to 3 lines, author at the bottom. The newest article of a list (and of the preset home) is `@article-featured.latte` over the whole row (image col-lg-7 16:9, title display-6, perex, author with a 56px avatar; no background, like blog.nette.org), the rest are cards. The list has the categories as pills under the title (`.blog-pill` in project-preset front.scss, the selected one `.active`). The detail is a 48rem column with a wide cropped image (21:9) and a line of author, date and category; `TimeAgoExtension` adds the Latte filter `|ago` (one message for one and one for more per unit, enough for Czech "před" and English "ago").
- Categories (2026-10-24, the user wants less clicking): written right in the article form (`new_category` under the select, a Bootstrap collapse "+ New category", open after a validation error via `ArticleTemplate::$newCategory`); `CategoryRepository::getOrCreate()` reuses a category of the same name (case-insensitive collation) or creates it with a `Strings::webalize()` slug (ext-intl, only suggested; without it a plain a-z0-9 slug), numbered when taken; the new category wins over the select. The categories page stays for renaming and deleting but is not in the menu: the button "Categories" in the heading of the articles (`{block buttons}` of `@datagrid-heading.latte`, `{plink}` because `n:href` of a component cannot link a presenter) and "← Articles" back. Comments per article: `getArticlesFluent()` adds `comments_total` and `comments_pending` (subqueries) for the grid column "Comments" (state badge Allowed/Disallowed, count, "%d pending" badge); the state badge itself switches the comments (signal `toggleComments!`, link from `getToggleCommentsLink()`, which the preset script makes null without the write privilege), because separate Allow/Disallow buttons next to Delete were unclear (user, 2026-10-24); the edit offcanvas of an existing article has pills "Article | Comments (n)" (a tab outside the form, so no field with an error is hidden); the comments tab (`CommentRepository::getByArticle()`, all comments) approves and deletes by signals of ArticleControl (`approveComment`, `confirmDeleteComment` → inline confirmation, `cancelDeleteComment`, `deleteComment`) that redraw only the snippets `comments` and `commentCount` (and the grid), never the form, so unsaved edits stay; the comment of a signal gives the article (`commentsArticleId`). Comments in the administration are basic Texy like on the website (`ArticleTemplate::$commentHtml` via TexyFormatter); the comments grid shows their plain text (`CommentControl::toPlainText()`: rendered, tags stripped, truncated), also in the delete dialog. In project-preset the buttons (`<div class="article-comment-actions`) get `n:if="$writeAllowed"` from netis/tools/preset-permission.php. There is no comments page any more (removed 2026-10-24 by the user: comments are managed with their article only; no `comments-read/write` privileges, the menu is the single item "Blog"). `Backend/Blog/CommentBadge` (registered in `6.menu.neon`, tag `sidebar.badge`, autowired: no) returns `[[link: 'Blog:', count: countPending(), label: 'Comments waiting for approval']]` for the menu badge and the navbar bell of project-backend-ui; it is duck-typed, so the blog needs no backend-ui class.
- Comment replies like nette.org (2026-10-24): approved comments are numbered by their order (`commentNumbers`, anchors `#comment-N`; numbers shift when an older comment is deleted or unapproved, accepted by the user), `Front/Blog/CommentReferences` links "#N" (and the author name after it) in the Texy HTML text only (not in tags, links, code) and collects replies ("Replies: #3 Name"), test `tests/Presentation/Front/Blog/CommentReferences.phpt`; "Reply" (`data-comment-reply`) is handled by project-preset front.js, the form box has `id="comment-form"`.
- A signed-in user comments without the name and email fields (`BlogPresenter::getSignedInAuthor()` from the identity data `username`/`email` of project-auth; the values are taken from the identity on submit, never from the request; the template shows "You are commenting as %s."). Comments are allowed per article only (`blog_article.comments_enabled`, "Allow comments" in the article form); there is no global switch and no dependency on project-settings (the setting `blog.comments_enabled` was removed on purpose, 2026-10-24).
- `migrations-permission/` holds the privileges of the administration for the `resources` table of drago-ex/project-permission (`INSERT IGNORE`). It is not run by `db:migrate --packages`; the user imports it by hand (`db:migrate vendor/drago-ex/<package>/migrations-permission`). The rows must match what the `Authorization` trait checks: `{action}-read` of the presenter's pages and `{component}-read/write` of its components; a new page or component needs a new file there (executed files must not change), and a translation of its description in `cs.neon`.

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include phpstan/phpstan-nette (^2.0), drago-ex/project-tools (^1.0).

## Essential Commands

```bash
composer phpstan                # phpstan analyse
```

## Tests

This repository does not currently define a test script in `composer.json` or contain a `tests/` directory.
Do not claim behavior is covered by tests unless you find and run a relevant suite. When adding a test suite, follow the package’s existing Nette Tester setup if one is present and add an explicit Composer script.

## Installation Hooks

Composer install and update hooks invoke Drago project setup/cleanup commands. Inspect those scripts and their effects before invoking Composer operations that trigger them; they may modify generated project files.

## Conventions & Rules

- Preserve backward compatibility unless the task explicitly permits a breaking change. Check callers and public signatures before changing behavior.
- Follow the existing PHP style: strict types, explicit parameter and return types, tabs for indentation, and single-quoted strings where practical. Match nearby code when a convention is unclear.
- Keep package-specific configuration, resources, and migrations in their established directories. Do not edit generated or consuming-project files without confirming they are part of this repository’s intended output.
- Add or update focused tests for behavioral changes when this package has a runnable test setup. Do not invent test commands; use only the scripts and configuration present here.
- Update README examples and Composer metadata when a public API or supported requirement changes.
