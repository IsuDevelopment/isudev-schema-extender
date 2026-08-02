# Changelog

## 0.2.5 — 2026-08-02

- Initial standalone release; extracted from the Kormas monorepo.
- Added GitHub auto-update (Plugin Update Checker v5 + release assets).
- Added release workflow: tag and versioned zip built from the plugin header on push to `main`.
- Added agent documentation (`AGENTS.md`, `.agents/`) and standalone lint/build config.
- Made the plugin Composer-installable from the git repository: `build/` is committed and
  `.gitattributes` trims archives to the runtime files.
