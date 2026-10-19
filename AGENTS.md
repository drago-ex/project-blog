# Agent Guide

Use this file as package-specific guidance when working in this repository. Keep changes aligned with the existing public API, project conventions, and supported PHP range.

## Package Overview
- **Package**: `drago-ex/project-blog`
- **Type**: drago-tools-resource
- **Purpose**: Blog articles and comments for Drago Project.
- **PHP**: >=8.3 <9
- **Runtime dependencies**: nette/application (^3.2), nette/di (^3.2), drago-ex/application (^3.0), drago-ex/component (^2.0), drago-ex/database (^3.0), drago-ex/datagrid (^1.0), drago-ex/form (^2.0), drago-ex/migration (^2.3), drago-ex/project-auth (^2.0), drago-ex/project-backend (^2.0), drago-ex/project-settings (^1.0), drago-ex/translator (^4.0), drago-ex/utils (^2.0)
- **License**: MIT

## Repository Layout
- `permission.php`: basic access rules for `drago-ex/permission`, declared in `extra.drago-permission` and generated into the project by `create-permission --packages`.
- `migrations/`: database migrations.
- `resources/`: application resources contributed to the consuming Drago project.
- `.github/`: CI workflows; keep their PHP versions and commands consistent with Composer and the package scripts.
- `composer.json`: package metadata, dependency constraints, autoloading, and available Composer scripts.

## Key Parts
- `blog_article` and `blog_comment` (migration `001_blog.sql`); the migration also inserts the setting `blog.comments_enabled`, so it needs the `settings` table of project-settings and runs after it (`migration-priority` 110; packages without a priority have 100 and the blog name sorts before settings).
- Backend `Backend:Blog` (administrators, writes checked with `blog-write`) manages articles with the datagrid; front `Front:Blog` (guests allowed on the whole resource) lists published articles and shows a detail by slug.
- The package ships no `PermissionProvider` classes and no config registering them: they are generated from `permission.php`, and the project (`project-permission` or the `drago-ex/permission` config) registers them. The former `blog-read`/`blog-view`/`blog-comment` privileges were dropped: nothing checked them, and there is no comment form yet.

## Dependencies

Composer constraints describe the package contract. Before changing integration behavior, inspect the source and the actual call sites in this package; do not infer runtime usage solely from dependency declarations.
Development dependencies include phpstan/phpstan-nette (^2.0), drago-ex/project-tools (^2.0).

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
