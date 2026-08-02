# Decisions

## 1. Standalone repository (2026-08-02)
Extracted from the Kormas monorepo so the plugin has its own release cycle and can be installed on
any Yoast-powered site. Consequence: the repo carries its own lint config, build and release CI
instead of inheriting the monorepo's.

## 2. Version reset to 0.2.5
The monorepo version (`1.1.0`) described an internal package. The standalone plugin starts a fresh,
pre-1.0 release line at `0.2.5`, tracked from now on in `CHANGELOG.md`.

## 3. Auto-update via PUC v5 + GitHub Releases
Same setup as `isu-wp-connector`: Plugin Update Checker points at the GitHub repo and uses release
assets. The checker only boots in `is_admin()` or cron, so the front end pays nothing. `vendor/` is
not committed — CI installs it into the release zip.

## 4. Extend Yoast instead of owning the graph
Yoast already emits `WebPage`, `Organization`, `WebSite`, breadcrumbs and images. Emitting a second
JSON-LD graph would duplicate entities and split identifiers, so the plugin only adds graph pieces
and one `about` reference.

## 5. Entity features, not a "service plugin"
`Service` is one module behind a shared architecture (meta + panel + graph piece). New entities are
added as sibling modules rather than by growing the Service code.

## 6. Typed area objects with a legacy text fallback
Free-text `areaServed` cannot express `@type`. Areas are stored as `{ type, name }` objects; the old
textarea meta stays readable as Schema.org Text until an editor migrates the page through the
repeater. No silent data loss, no automatic guessing of area types.

## 7. No prices, ratings or reviews in the catalog
`OfferCatalog` describes the visible scope of a service. Emitting prices or ratings without matching
on-page content is a structured-data violation, so those fields are intentionally unsupported.

## 8. `build/` is generated, not committed
The editor bundle is produced by `@wordpress/scripts` in CI and shipped inside the release zip. This
keeps diffs readable and prevents stale bundles in the repo.
