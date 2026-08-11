# Conventions

## Schema rules (hard requirements)

- Yoast SEO owns the primary graph. **Never emit a second JSON-LD script.**
- Add entities through `wpseo_schema_graph_pieces`. Typed features own their explicit graph
  relations; Custom Schema preserves relations supplied in validated JSON.
- Reuse Yoast's graph identifiers for provider and primary image. Service entity IDs end in
  `#service` (`Service_Schema_Piece::get_schema_id()`).
- Only emit a node when real data exists — no empty `hasOfferCatalog`, no empty arrays.
- Do not add `Offer` prices, ratings or reviews unless equivalent visible, verifiable content
  exists on the page. The catalog describes service scope, not transactions.
- Typed areas accept only `City`, `AdministrativeArea` and `Country`. Store `{ type, name }`;
  translate to `@type` only while generating JSON-LD.
- `_isudev_yoast_service_area_served` is a read-only legacy source. Keep it as Schema.org Text
  until an editor edits the repeater, then clear it.
- Custom Schema accepts arbitrary Schema.org properties but requires valid JSON and `@type`,
  rejects nested `@context`, caps input at 20 nodes / 100,000 characters and may not reuse
  Yoast-owned top-level IDs.
- Invalid Custom Schema remains editable but generates no nodes. Never partially emit a source
  that failed validation.

## Feature modularity

Typed schema entities are feature modules with their own meta fields, editor panel, graph piece and
relations. The generic Custom feature is the fallback for uncommon entity types and must not grow
type-specific business rules. Reusable controls live in `src/components/` and must be configured
through props, never coupled to one feature.

## PHP

- WordPress Coding Standards (`composer lint:php`), tabs, Yoda-free but WPCS-clean.
- `declare( strict_types = 1 );` and a namespace in every class file; `defined( 'ABSPATH' ) || exit;`
  in every entry file.
- Classes are `final`, mostly static, one class per file, `class-*.php` naming.
- Post meta: private (`_` prefix), `show_in_rest` with an explicit schema, a sanitize callback and
  an `edit_post` auth callback. No exceptions.
- Features default to every public post type that WordPress considers front-end viewable. Preserve
  their filters as the boundary for site-specific restrictions.
- Escape on output, sanitize on input.
- External integrations call `Custom\Integration_API`; they never read or write the private meta
  keys directly.

## JavaScript

- The sidebar is **editor-only**, built from `src/` with `@wordpress/scripts`. Never edit `build/`.
- Read and write meta through `core/editor` selectors/dispatch — no direct REST calls.
- Group imports as WordPress dependencies / internal dependencies (`@wordpress/dependency-group`).
- Run `npm run format` before `npm run lint:js`; both must pass.
- Text domain: `isudev-schema-extended` for every translatable string.
- English is the source language for every PHP and JavaScript `msgid`; Polish exists only in the
  `pl_PL` catalogs. Run `npm run i18n` after changing a translatable string.

## Comments

Short, factual, explain **why**. No restating code, no banners, no history in comments. If an
explanation needs more than a line or two, it belongs in `.agents/` docs and the comment just
points there.
