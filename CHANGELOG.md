# Changelog

## 0.3.1 — 2026-08-11

- Enabled Service and Custom Schema out of the box for every public, front-end-viewable post type.
- Updated the PHP_CodeSniffer development lock to the security-patched 3.13.6 release.

## 0.3.0 — 2026-08-03

- Added an advanced Custom Schema editor that merges arbitrary validated Schema.org nodes into
  Yoast's existing graph without emitting a second JSON-LD script.
- Added browser and server validation, safe placeholder resolution, Yoast-owned ID protection and
  a fail-closed renderer that ignores invalid or disabled custom JSON.
- Added a capability-protected PHP integration API for future MCP read, validate and update
  abilities.
- Scoped formatting commands to editor sources so they no longer rewrite release or PHP
  configuration files.

## 0.2.5 — 2026-08-02

- Initial standalone release; extracted from the Kormas monorepo.
- Added GitHub auto-update (Plugin Update Checker v5 + release assets).
- Added release workflow: tag and versioned zip built from the plugin header on push to `main`.
- Added agent documentation (`AGENTS.md`, `.agents/`) and standalone lint/build config.
- Made the plugin Composer-installable from the git repository: CI attaches the built assets to the
  release tag and `.gitattributes` trims archives to the runtime files.
