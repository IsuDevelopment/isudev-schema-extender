# Architecture

Two halves: **PHP** registers post meta and injects graph nodes into Yoast's schema; **JS** renders
the editor sidebar that writes that meta over the REST API. No front-end JS, no second JSON-LD
script — Yoast stays the only schema output.

## Bootstrap

`isudev-schema-extended.php` defines `ISUDEV_SCHEMA_EXTENDED_FILE` / `_VERSION`, loads Composer
autoload (Plugin Update Checker, admin/cron only), requires the classes and calls
`Plugin::register()`.

`Plugin::register()` wires five callbacks across three hooks:

| Hook | Callback | Purpose |
| --- | --- | --- |
| `init` | `Plugin::load_textdomain` | Load bundled PHP translations for non-WordPress.org installs |
| `init` (100) | `Service\Meta_Fields::register` | Register private REST post meta after conventional CPT registration |
| `init` (100) | `Custom\Meta_Fields::register` | Register Custom Schema toggle + JSON source after conventional CPT registration |
| `enqueue_block_editor_assets` | `Editor_Sidebar::enqueue` | Load `build/index.js` + CSS and `window.isudevSchemaExtended` |
| `wp_abilities_api_categories_init` | `Abilities::register_category` | Register the `isudev-schema` category |
| `wp_abilities_api_init` | `Abilities::register_abilities` | Register the four Custom Schema abilities |
| `wpai_register_features` | `Wp_Ai_Integration::register_feature` | Add the suggestion toggle to the WordPress AI plugin, when active |
| `plugins_loaded` (20) | `Plugin::register_yoast_integration` | Load graph code only if Yoast is active |

The Yoast half is required lazily and guarded by `class_exists( Abstract_Schema_Piece )`, so the
plugin is inert without Yoast.

## Schema flow

```
page render
  → Yoast builds its graph
  → wpseo_schema_graph_pieces  → Schema_Integration::add_service_piece()
                                 → Service_Schema_Piece::is_needed() / generate()
                              → Custom\Schema_Integration::add_custom_piece()
                                 → Custom_Schema_Piece::is_needed() / generate()
  → wpseo_schema_webpage       → Schema_Integration::link_webpage_to_service()
                                 → WebPage.about → { "@id": "<canonical>#service" }
```

`link_webpage_to_service()` appends to `about` without overwriting existing values and never
touches FAQ `mainEntity`. Provider, primary image, language and canonical are reused from Yoast's
`Meta_Tags_Context` — the plugin does not invent identifiers.

The Custom piece parses one stored JSON source into up to 20 graph nodes. It accepts one object, a
node list or an outer `@graph`, resolves context placeholders and rejects invalid input or
Yoast-owned top-level IDs. It returns an empty list on any error, so a malformed custom source
cannot partially alter the public graph.

## Editor flow

```
PluginSidebar "Schema Extended" (src/index.js)
  → ServicePanel (src/features/service/service-panel.js)
      → core/editor meta via useSelect/useDispatch
      → TypedNameRepeater (areas)   → _isudev_yoast_service_areas
      → OfferRepeater (catalog)     → _isudev_yoast_service_offers
  → CustomSchemaPanel (src/features/custom/custom-schema-panel.js)
      → immediate JSON diagnostics  → _isudev_schema_custom_json
```

The sidebar mounts for the union of feature-supported post types. Both features default to all
public post types that WordPress considers front-end viewable; their existing filters can override
the lists independently. The plugin enables `custom-fields` support for selected registered types
so the block editor can persist private REST metadata.

English is the source locale. PHP loads the bundled `languages/isudev-schema-extended-pl_PL.mo`;
the editor passes the same directory to `wp_set_script_translations()`, which loads the JSON catalog
mapped to the compiled `build/index.js` bundle.

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
| `_isudev_schema_custom_enabled` | bool | Master switch for generic custom graph nodes |
| `_isudev_schema_custom_json` | string | Editable JSON source, max 100,000 characters |

## Extension points

| Filter | Purpose |
| --- | --- |
| `isudev_schema_extended_service_post_types` | Add post types for the Service feature |
| `isudev_yoast_services_post_types` | Legacy alias, applied first — keep working |
| `isudev_schema_extended_custom_post_types` | Add post types for the Custom Schema feature |

## Entity extension strategy

Use Custom Schema for less common arbitrary Schema.org nodes. Add a typed sibling feature only
when a recurring entity needs a safer guided UI, derived Yoast relationships or domain-specific
validation. Reusable controls go to `src/components/`, never into a feature folder.

## External integration

`Custom\Integration_API` is the stable boundary for another plugin. It provides authorized read
and update operations plus write-free validation. Other plugins, including WP Content Bridge, must
not couple themselves to the private meta keys.

## Abilities and AI suggestions

```
Abilities (isudev-schema/*) ── permission: supported post type + edit_post
  get / validate / update  → Custom\Integration_API
  suggest                  → Ai\Schema_Suggester
                               → post content + YoastSEO()->meta->for_post()->schema + current source
                               → wp_ai_client_prompt()->as_json_response() → { analysis, gaps, proposed_source }
                               → strip reviews/ratings/offers/prices → Graph_Parser::parse
                               → writes_performed: false
```

Abilities are adapters only: storage and validation stay in `Custom\`. Each ability sets
`meta.mcp.public` (filter `isudev_schema_extended_mcp_public`), which puts it on the MCP Adapter
default server. WP Content Bridge keeps its own `wpcb/*` Custom Schema abilities on its own server.

Editor flow: `SchemaSuggestion` → `runAbility()` POST `/wp-abilities/v1/abilities/<name>/run` with
the unsaved content and JSON → modal → "Replace" calls `editPost( meta )`; the post save persists it.

`Schema_Suggester::is_enabled()` is evaluated at run time, not registration time: with the
WordPress AI plugin active, its loader calls `Wp_Ai_Feature::register()` on `init` (15) only when the
feature is on; without it the feature is on. The filter `isudev_schema_extended_ai_suggestions_enabled`
wins in both cases. `has_provider()` is a support check with no API call.
