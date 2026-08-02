# IsuDev Schema Extended

WordPress plugin that extends the **Yoast SEO** schema graph with configurable, page-level
entities — edited in the block editor, rendered by Yoast.

Yoast remains the owner of `WebPage`, `FAQPage`, `Organization`, `WebSite`, images and breadcrumbs.
This plugin only adds entity nodes to that existing graph and links them with `WebPage.about`; it
never outputs a second JSON-LD script.

`Service` is the first entity module. The architecture (meta fields + editor panel + graph piece)
is designed so further entities can be added as sibling modules.

## Why

Editors need page-level structured data (what service this page describes, where it is offered,
what it covers) without hand-written JSON-LD, and without fighting the SEO plugin that already
renders the graph. The plugin gives them typed fields in the editor sidebar and turns those fields
into valid Schema.org output on the server.

## Requirements

- WordPress 6.9+
- PHP 8.4+
- Yoast SEO (`wordpress-seo`) active — the plugin stays inert without it

## Installation

Pick one channel per site — do not mix them.

### Composer (for Composer-managed sites)

```json
{
	"repositories": {
		"isudev-schema-extended": {
			"type": "vcs",
			"url": "https://github.com/IsuDevelopment/isudev-schema-extender"
		}
	},
	"require": {
		"isudev/schema-extended": "^0.2.5"
	}
}
```

```bash
composer require isudev/schema-extended:^0.2.5
```

`composer/installers` puts it in the site's plugin directory (`schema-extended`). Composer owns the
version here: the plugin's own `vendor/` is absent, so the self-updater stays off and Composer is
the only thing that moves the version. Use `dev-main` instead of `^0.2.5` to track the branch.

### Release zip (for everything else)

Download `isudev-schema-extended-<version>.zip` from
[Releases](https://github.com/IsuDevelopment/isudev-schema-extender/releases) and install it in
**Plugins → Add New → Upload Plugin**. Updates are automatic afterwards: the plugin checks this
repository's releases from wp-admin and cron, and appears in the normal WordPress update screen.

## Editor fields

The **Schema Extended** sidebar is available on pages and stores:

- enabled state;
- service name and type;
- description;
- typed service areas — repeatable `City`, `AdministrativeArea` or `Country` rows;
- newline- or comma-separated brands;
- an optional offer catalog name;
- up to 20 catalog items with a required name and optional description.

Canonical URL, page relation, provider, language and primary image are derived from Yoast's current
schema context, so identifiers never diverge from the rest of the graph.

### Service areas

Areas are stored as private REST metadata objects with `type` and `name`. JSON-LD serialization
happens only on the server:

```json
"areaServed": [
  { "@type": "City", "name": "Wrocław" },
  { "@type": "AdministrativeArea", "name": "powiat trzebnicki" }
]
```

Legacy textarea values remain readable as Schema.org text until an editor assigns explicit types in
the repeater; editing the repeater migrates that page to the structured field.

### Offer catalog

Catalog items are serialized only when at least one item has a non-empty name. Prices and
availability are intentionally unsupported — the catalog describes the visible scope of a service,
not transactional product offers. Every item should also exist in the page's visible content.

```json
"hasOfferCatalog": {
  "@type": "OfferCatalog",
  "name": "Zakres usług monitoringu we Wrocławiu",
  "itemListElement": [
    {
      "@type": "Offer",
      "itemOffered": { "@type": "Service", "name": "Projekt i montaż nowego systemu monitoringu" }
    }
  ]
}
```

## Supported post types

Pages are supported by default. Add another REST-enabled post type with:

```php
add_filter(
	'isudev_schema_extended_service_post_types',
	static fn( array $post_types ): array => [ ...$post_types, 'service' ]
);
```

The post type must support `custom-fields` so WordPress can persist REST-exposed post meta.

## Development

```bash
composer install   # update checker + PHPCS
npm install
npm run start      # watch src/ -> build/
npm run build      # production bundle
npm run lint:js    # ESLint + Prettier
composer lint:php  # WordPress Coding Standards
```

Entity PHP lives in `includes/<feature>/`, its editor panel in `src/features/<feature>/`, and
reusable controls in `src/components/`. `build/`, `vendor/` and `node_modules/` are generated.

## Releasing

Bump `Version:` in `isudev-schema-extended.php` (mirrored in `composer.json` and `package.json`),
add a `CHANGELOG.md` entry and push to `main`. CI tags `v<version>` and publishes
`isudev-schema-extended-<version>.zip`. Details: [.agents/RELEASE.md](.agents/RELEASE.md).

## Documentation

- [AGENTS.md](AGENTS.md) — entry point for AI agents and contributors
- [.agents/](.agents/) — architecture, code map, conventions, decisions, release
- [CHANGELOG.md](CHANGELOG.md)

## License

GPL-3.0-or-later.
