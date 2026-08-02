# Conventions

## Schema rules (hard requirements)

- Yoast SEO owns the primary graph. **Never emit a second JSON-LD script.**
- Add entities through `wpseo_schema_graph_pieces`; link them with `WebPage.about` without
  replacing FAQ `mainEntity` questions.
- Reuse Yoast's graph identifiers for provider and primary image. Service entity IDs end in
  `#service` (`Service_Schema_Piece::get_schema_id()`).
- Only emit a node when real data exists — no empty `hasOfferCatalog`, no empty arrays.
- Do not add `Offer` prices, ratings or reviews unless equivalent visible, verifiable content
  exists on the page. The catalog describes service scope, not transactions.
- Typed areas accept only `City`, `AdministrativeArea` and `Country`. Store `{ type, name }`;
  translate to `@type` only while generating JSON-LD.
- `_isudev_yoast_service_area_served` is a read-only legacy source. Keep it as Schema.org Text
  until an editor edits the repeater, then clear it.

## Feature modularity

Each schema entity is a feature module: its own meta fields, editor panel, graph piece and graph
relations. PHP in `includes/<feature>/`, editor UI in `src/features/<feature>/`. `Service` is the
first feature, not the plugin identity. Reusable controls live in `src/components/` and must be
configured through props, never coupled to `Service`.

## PHP

- WordPress Coding Standards (`composer lint:php`), tabs, Yoda-free but WPCS-clean.
- `declare( strict_types = 1 );` and a namespace in every class file; `defined( 'ABSPATH' ) || exit;`
  in every entry file.
- Classes are `final`, mostly static, one class per file, `class-*.php` naming.
- Post meta: private (`_` prefix), `show_in_rest` with an explicit schema, a sanitize callback and
  an `edit_post` auth callback. No exceptions.
- Escape on output, sanitize on input.

## JavaScript

- The sidebar is **editor-only**, built from `src/` with `@wordpress/scripts`. Never edit `build/`.
- Read and write meta through `core/editor` selectors/dispatch — no direct REST calls.
- Group imports as WordPress dependencies / internal dependencies (`@wordpress/dependency-group`).
- Run `npm run format` before `npm run lint:js`; both must pass.
- Text domain: `isudev-schema-extended` for every translatable string.

## Comments

Short, factual, explain **why**. No restating code, no banners, no history in comments. If an
explanation needs more than a line or two, it belongs in `.agents/` docs and the comment just
points there.
