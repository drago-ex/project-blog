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
- Articles have a cover image (`image`, a file name) and an author (`author_id` → `users`, ON DELETE SET NULL). `Model/Blog/ArticleImages` stores uploads in `www/upload/blog/` (resized to fit 1600 × 1200 with Nette Image, random name, deleted on replace, remove and article delete); it needs ext-gd, which is only suggested in composer.json, so hosts without GD can still install. `ArticleValues::$image` and `$author_id` are declared without a default: uninitialized, they are not saved (`save()` casts the values to an array), so an edit keeps them. `Model/Blog/ArticleAuthors` gives the name and the Gravatar URL, so email addresses never reach templates. `Front/Blog/@article-card.latte` is the card of the blog list and of the project-preset home page; `TimeAgoExtension` adds the Latte filter `|ago` (one message for one and one for more per unit, enough for Czech "před" and English "ago").
- Comments are allowed per article only (`blog_article.comments_enabled`, "Allow comments" in the article form); there is no global switch and no dependency on project-settings (the setting `blog.comments_enabled` was removed on purpose, 2026-10-24).
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
