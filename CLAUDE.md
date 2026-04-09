# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin that imports Dev.to articles into WordPress posts via the Forem API v1. Admin page lives under **Posts > Dev.to Importer**. Import is triggered via AJAX (`wp_ajax_devto_wp_importer_import_articles`) and runs synchronously with a transient-based lock to prevent concurrent imports.

## Commands

```bash
composer install                # Install all dependencies (dev + prod)
composer phpcs                  # Run PHPCS with WordPress coding standards
composer phpcbf                 # Auto-fix coding standard violations
composer install --optimize-autoloader --no-dev -q   # Production install
bin/build.sh                    # Build release ZIP (runs composer prod install internally)
```

CI runs PHPCS on changed PHP files only (`.github/workflows/phpcs.yml`).

## Architecture

**Namespace:** `WeLabs\DevtoWpImporter` — PSR-4 autoloaded from `includes/`.

**Bootstrap chain:** `devto-wp-importer.php` -> `welabs_devto_wp_importer()` -> `DevtoWpImporter::init()` (singleton). Classes are instantiated in `init_classes()` and accessed via magic `__get` on the singleton container:

- `api_client` (`ApiClient`) — Handles Dev.to API communication with retry/backoff. API key stored encrypted in `wp_options` using `wp_salt()`.
- `importer` (`Importer`) — Core import engine. Receives `ApiClient` via constructor. Fetches article list, then for each: checks for existing WP post by `_devto_wp_importer_devto_id` meta, compares `edited_at` to decide skip/update/create, fetches full article content per-item (list endpoints don't include `body_html`).
- `admin` (`Admin`) — Settings page, AJAX endpoint, admin notices. Receives `Importer` via constructor.
- `scripts` (`Assets`) — Enqueues admin JS/CSS only on the plugin's page (`posts_page_devto-wp-importer`).

**Import flow:** List all articles (paginated) -> for each: find existing post by meta -> skip if `edited_at` unchanged -> otherwise fetch full article -> create or update WP post + meta + tags + cover image sideload.

**Post meta prefix:** `_devto_wp_importer_` (e.g., `_devto_wp_importer_devto_id`, `_devto_wp_importer_devto_edited_at`).

**Options prefix:** `devto_wp_importer_` (e.g., `devto_wp_importer_api_key`, `devto_wp_importer_post_status`).

**Hook prefix:** `devto_wp_importer_` for all actions and filters.

## Coding Standards

- WPCS enforced via `phpcs.xml`. PHP 7.4+ compatibility required. Minimum WP version: 5.4.
- Text domain: `devto-wp-importer`.
- Use strict comparisons (`===`, `!==`) and strict `in_array(..., true)`.
- Sanitize input early, escape output late. Use WP APIs (`get_posts`, `update_post_meta`) — no raw SQL.
- All direct-entry PHP files must start with `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- New service classes go in `includes/` and get wired from `DevtoWpImporter::init_classes()`.
- Register behavior through hooks (`add_action`, `add_filter`), not direct execution.
- Use class-method callbacks for hooks: `[ $this, 'method_name' ]`.

## Key Extensibility Points

**Filters:** `devto_wp_importer_should_import_article`, `devto_wp_importer_article_post_data`, `devto_wp_importer_post_status`, `devto_wp_importer_article_meta_data`, `devto_wp_importer_api_request_args`.

**Actions:** `devto_wp_importer_before_import`, `devto_wp_importer_after_import`, `devto_wp_importer_article_imported`, `devto_wp_importer_article_updated`, `devto_wp_importer_article_skipped`.
