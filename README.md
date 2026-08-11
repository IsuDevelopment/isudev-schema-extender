# IsuDev Schema Extended

WordPress plugin that extends the **Yoast SEO** schema graph with configurable, content-level
entities — edited in the block editor, validated on the server and rendered by Yoast.

Yoast remains the owner of `WebPage`, `FAQPage`, `Organization`, `WebSite`, images and breadcrumbs.
This plugin only adds entity nodes to that existing graph. The typed Service module links its node
with `WebPage.about`; advanced Custom Schema nodes keep the relations supplied in their JSON. The
plugin never outputs a second JSON-LD script.

`Service` is the first entity module. The architecture (meta fields + editor panel + graph piece)
is designed so further entities can be added as sibling modules.

## Why

Editors need content-level structured data (what service this content describes, where it is offered,
what it covers) without hand-written JSON-LD, and without fighting the SEO plugin that already
renders the graph. The plugin gives them typed fields in the editor sidebar and turns those fields
into valid Schema.org output on the server.

## Requirements

- WordPress 6.9+
- PHP 8.4+
- Yoast SEO (`wordpress-seo`) active — the plugin stays inert without it

## Languages

English is the source language. A complete Polish (`pl_PL`) translation for PHP diagnostics and
the block-editor sidebar is bundled with the plugin and selected automatically from the current
WordPress locale, including the per-user admin language. Translation catalogs are regenerated with
`npm run i18n` using WP-CLI.

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
		"isudev/schema-extended": "^0.3.2"
	}
}
```

```bash
composer require isudev/schema-extended:^0.3.2
```

`composer/installers` puts it in the site's plugin directory (`schema-extended`). Composer owns the
version here: the plugin's own `vendor/` is absent, so the self-updater stays off.

Always use a tagged constraint. Compiled editor assets are built by CI and exist only in release
tags, so `dev-main` would install a plugin without its sidebar.

### Release zip (for everything else)

Download `isudev-schema-extended-<version>.zip` from
[Releases](https://github.com/IsuDevelopment/isudev-schema-extender/releases) and install it in
**Plugins → Add New → Upload Plugin**. Updates are automatic afterwards: the plugin checks this
repository's releases from wp-admin and cron, and appears in the normal WordPress update screen.

## Editor fields

The **Schema Extended** sidebar is available on every public post type that WordPress considers
front-end viewable. Its dedicated schema icon opens a typed Service panel and an advanced Custom
Schema panel.

The Service panel stores:

- enabled state;
- service name and type;
- description;
- typed service areas — repeatable `City`, `AdministrativeArea` or `Country` rows;
- newline- or comma-separated brands;
- an optional offer catalog name;
- up to 20 catalog items with a required name and optional description.

Canonical URL, page relation, provider, language and primary image are derived from Yoast's current
schema context, so identifiers never diverge from the rest of the graph.

### Custom Schema

Custom Schema accepts one Schema.org object, a list of objects or a complete `@graph` wrapper in
a large JSON editor below the Service panel. Its own toggle can disable rendering without deleting
the source. The sidebar validates JSON immediately; the server validates it again and fails closed,
so invalid data stays editable but never reaches Yoast's output.

Every graph node requires `@type`. `@id` is optional: the renderer creates a stable page-local
identifier from its position when it is absent. Relative fragment IDs such as
`#installation-video` are resolved against the current canonical URL.

Yoast remains the owner of `@context`. A pasted `https://schema.org` context is accepted on the
outer object and removed; nested or foreign contexts are rejected. Up to 20 nodes and 100,000
characters are accepted. Custom top-level nodes may not reuse Yoast's WebPage, WebSite,
Organization, Person, Article, primary-image or breadcrumb identifiers, nor the typed Service
module's identifier. Unknown context placeholders are also rejected.

Context-sensitive relations can use these placeholders:

- `{{canonical}}`;
- `{{webpage_id}}`;
- `{{site_url}}`;
- `{{website_id}}`;
- `{{organization_id}}`;
- `{{primary_image_id}}`.

Example:

```json
{
  "@type": "VideoObject",
  "@id": "{{canonical}}#installation-video",
  "name": "Montaż systemu monitoringu",
  "mainEntityOfPage": { "@id": "{{webpage_id}}" }
}
```

Only describe information visible on the page. The generic editor deliberately does not certify
that an arbitrary Schema.org type qualifies for a Google rich result.

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

Every public, front-end-viewable post type is supported by default. Use the filters to override the
Service or Custom Schema list independently, for example to restrict Service to pages:

```php
add_filter(
	'isudev_schema_extended_service_post_types',
	static fn( array $post_types ): array => [ 'page' ]
);
```

Custom Schema has its own equivalent filter:

```php
add_filter(
	'isudev_schema_extended_custom_post_types',
	static fn( array $post_types ): array => [ 'page', 'post' ]
);
```

The plugin enables `custom-fields` support for selected registered post types so WordPress can
persist its private REST-exposed metadata. Existing capability and sanitization checks still apply.

## Integration API

External plugins should use `IsuDev\SchemaExtended\Custom\Integration_API` instead of reading
post meta. The capability-protected contract exposes:

- `get_configuration( $post_id )`;
- `validate_source( $json )`;
- `update_configuration( $post_id, $enabled, $json )`.

The update method refuses to enable invalid JSON and returns structured diagnostics in a
`WP_Error`. This is the intended boundary for future MCP abilities in a separate bridge plugin.

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
reusable controls in `src/components/`. Typed panels remain the preferred experience for common
entities; Custom Schema is the generic escape hatch. `build/`, `vendor/` and `node_modules/`
are generated.

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
