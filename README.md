# Sync Dev.to to WordPress

A WordPress plugin that imports your Dev.to articles into WordPress posts with one click. Features smart duplicate prevention via Dev.to article ID mapping and incremental updates on subsequent imports.

## Features

- **One-click import** from the WordPress admin dashboard (Tools → Dev.to Importer)
- **Duplicate prevention** — maps Dev.to article IDs to WordPress post meta (`_sdwp_devto_id`)
- **Smart updates** — only re-imports articles that have been edited since the last sync
- **Full content import** — title, HTML body, excerpt, tags, cover image
- **Cover image sideloading** — downloads and attaches cover images as featured images
- **Configurable** — choose post status, author, and import scope (published only or all)
- **Encrypted API key** — stored with AES-256-CBC encryption
- **Extensible** — filters and actions for customizing every step of the import

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory
2. Activate the plugin in WordPress → Plugins
3. Navigate to **Tools → Dev.to Importer**
4. Enter your Dev.to API key (get one at [dev.to/settings/extensions](https://dev.to/settings/extensions))
5. Click **Import Articles from Dev.to**

## Documentation

See [DEVELOPMENT-PLAN.md](DEVELOPMENT-PLAN.md) for detailed architecture documentation, API analysis, field mappings, and extensibility hooks.
