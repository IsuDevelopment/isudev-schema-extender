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

- WordPress 7.1+
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
		"isudev/schema-extended": "^0.4.0"
	}
}
```

```bash
composer require isudev/schema-extended:^0.4.0
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

### Explore & extend schema (AI)

When the site has an AI connector with text generation (**Settings → Connectors**, WordPress 7.0+
AI Client), the Custom Schema panel shows an **Explore & extend schema** button. It sends the
current editor content, the resolved Yoast graph and the current Custom Schema JSON to the
connector and opens a proposal: a short analysis, the missing entities and a complete replacement
JSON next to the current one. **Replace JSON in the editor** only changes the unsaved editor value —
nothing is stored until the post is saved.

The proposal passes the same server validation as manual JSON. Reviews, ratings, offers and prices
are always removed from AI proposals, because they need verifiable on-page content; the modal lists
what was removed. Without a connector the button stays disabled with a link to Connectors.

With the [WordPress AI plugin](https://github.com/WordPress/ai) active, the feature appears as
**Custom Schema suggestions** in its settings and follows that toggle. Without it, the feature is on
whenever a connector exists. Override either way:

```php
add_filter( 'isudev_schema_extended_ai_suggestions_enabled', '__return_false' );
```

Content leaves the site for the configured AI provider on every click.

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
`WP_Error`.

## Abilities and MCP

The plugin registers four abilities in the `isudev-schema` category. Every one requires
`edit_post` on the requested post:

| Ability | Does |
| --- | --- |
| `isudev-schema/get-custom-schema` | Read the saved toggle, JSON and validation |
| `isudev-schema/validate-custom-schema` | Validate proposed JSON without saving |
| `isudev-schema/update-custom-schema` | Save the toggle and/or JSON (enabled JSON must be valid) |
| `isudev-schema/suggest-custom-schema` | AI analysis and proposal; never saves (`writes_performed: false`) |

They are available over REST (`/wp-json/wp-abilities/v1/abilities/<name>/run`) and marked
`meta.mcp.public`, so the [MCP Adapter](https://github.com/WordPress/mcp-adapter) default server
lists them through `discover-abilities` and runs them through `execute-ability` — no extra MCP
plugin required.

Sites that also run WP Content Bridge, which has its own gated Custom Schema tools, can hide the
duplicates from MCP:

```php
add_filter(
	'isudev_schema_extended_mcp_public',
	static fn( bool $public, string $name ): bool => 'isudev-schema/suggest-custom-schema' === $name
		? $public
		: false,
	10,
	2
);
```

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
