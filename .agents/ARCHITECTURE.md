# Architecture

Two halves: **PHP** registers post meta and injects graph nodes into Yoast's schema; **JS** renders
the editor sidebar that writes that meta over the REST API. No front-end JS, no second JSON-LD
script — Yoast stays the only schema output.

## Bootstrap

`isudev-schema-extended.php` defines `ISUDEV_SCHEMA_EXTENDED_FILE` / `_VERSION`, loads Composer
autoload (Plugin Update Checker, admin/cron only), requires the classes and calls
`Plugin::register()`.

`Plugin::register()` wires three hooks:

| Hook | Callback | Purpose |
| --- | --- | --- |
| `init` | `Service\Meta_Fields::register` | Register private REST post meta |
| `enqueue_block_editor_assets` | `Editor_Sidebar::enqueue` | Load `build/index.js` + CSS |
| `plugins_loaded` (20) | `Plugin::register_yoast_integration` | Load graph code only if Yoast is active |

The Yoast half is required lazily and guarded by `class_exists( Abstract_Schema_Piece )`, so the
plugin is inert without Yoast.

## Schema flow

```
page render
  → Yoast builds its graph
  → wpseo_schema_graph_pieces  → Schema_Integration::add_service_piece()
                                 → Service_Schema_Piece::is_needed() / generate()
  → wpseo_schema_webpage       → Schema_Integration::link_webpage_to_service()
                                 → WebPage.about → { "@id": "<canonical>#service" }
```

`link_webpage_to_service()` appends to `about` without overwriting existing values and never
touches FAQ `mainEntity`. Provider, primary image, language and canonical are reused from Yoast's
`Meta_Tags_Context` — the plugin does not invent identifiers.

## Editor flow

```
PluginSidebar "Schema Extended" (src/index.js)
  → ServicePanel (src/features/service/service-panel.js)
      → core/editor meta via useSelect/useDispatch
      → TypedNameRepeater (areas)   → _isudev_yoast_service_areas
      → OfferRepeater (catalog)     → _isudev_yoast_service_offers
```

The sidebar only mounts for post types returned by `Meta_Fields::get_supported_post_types()`
(`page` by default). Those post types must support `custom-fields`.

## Data model (post meta)

All keys are private (`_`-prefixed), REST-exposed, sanitized on write, `edit_post`-authorized.

| Key | Type | Notes |
| --- | --- | --- |
| `_isudev_yoast_service_enabled` | bool | Master switch for the page |
| `_isudev_yoast_service_name` | string | Service name |
| `_isudev_yoast_service_type` | string | `serviceType` |
| `_isudev_yoast_service_description` | string | Description |
| `_isudev_yoast_service_areas` | array | `{ type, name }`; type ∈ `City`, `AdministrativeArea`, `Country` |
| `_isudev_yoast_service_area_served` | string | **Legacy**, read-only compatibility source |
| `_isudev_yoast_service_brands` | string | Newline/comma separated |
| `_isudev_yoast_service_catalog_name` | string | `OfferCatalog.name` |
| `_isudev_yoast_service_offers` | array | `{ name, description }`, max 20, `name` required |

## Extension points

| Filter | Purpose |
| --- | --- |
| `isudev_schema_extended_service_post_types` | Add post types for the Service feature |
| `isudev_yoast_services_post_types` | Legacy alias, applied first — keep working |

## Adding a new entity feature

1. `includes/<feature>/` — meta fields, schema piece, graph integration.
2. `src/features/<feature>/` — the editor panel.
3. Compose the panel into the sidebar in `src/index.js`; require the PHP from `Plugin`.
4. Reusable controls go to `src/components/`, never into the feature folder.
