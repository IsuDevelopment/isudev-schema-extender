# AGENTS.md — IsuDev Schema Extended

Standalone WordPress plugin that extends the **Yoast SEO** schema graph with configurable,
page-level entities. `Service` is the first entity module — it is a feature, not the plugin
identity. Formerly lived in the Kormas monorepo (`packages/plugins/isudev-schema-extended`);
now released independently from this repository.

Read this file first. Everything deeper lives in [`.agents/`](.agents/).

## Agent docs (`.agents/`)

| Doc | What it holds |
| --- | --- |
| [.agents/ARCHITECTURE.md](.agents/ARCHITECTURE.md) | Runtime flow, hooks, data model |
| [.agents/CODE-MAP.md](.agents/CODE-MAP.md) | File-by-file map — where to make a change |
| [.agents/CONVENTIONS.md](.agents/CONVENTIONS.md) | Schema, PHP, JS and comment rules |
| [.agents/DECISIONS.md](.agents/DECISIONS.md) | Why the plugin is built this way |
| [.agents/RELEASE.md](.agents/RELEASE.md) | Versioning, CI release, auto-update |

**These docs are mandatory to maintain.** Every change must leave them true:

1. New file, class, hook or meta key → update `.agents/CODE-MAP.md` (and `ARCHITECTURE.md` if the
   flow changed).
2. Changed rule or convention → update `.agents/CONVENTIONS.md`.
3. Non-obvious trade-off → add a short entry to `.agents/DECISIONS.md`.
4. Removed code → delete the matching doc lines. Stale docs are treated as bugs.
5. **Every change gets a `CHANGELOG.md` entry** (root, one line, newest on top).
6. User-visible behaviour changed → update `README.md` too.

`CLAUDE.md` and `GEMINI.md` are pointers to this file. Do not duplicate content into them.

## Commands

```bash
composer install        # PUC (update checker) + PHPCS
npm install             # editor build toolchain
npm run build           # compile src/ -> build/   (never edit build/ by hand)
npm run start           # watch mode
npm run lint:js         # ESLint + Prettier
npm run format          # autofix JS formatting
composer lint:php       # PHPCS, WordPress Coding Standards
php -l <file>           # quick syntax check
```

`build/`, `vendor/` and `node_modules/` are generated and git-ignored. CI rebuilds them for the
release zip.

## Agent flow

1. Read `AGENTS.md` → the relevant `.agents/` doc → only then the code.
2. Make the smallest change that solves the task. Do not refactor unrelated code.
3. Run the linters for whatever you touched (`lint:js` for JS, `composer lint:php` for PHP), and
   `npm run build` if you touched `src/`.
4. Update the docs listed above **in the same change**, plus `CHANGELOG.md`.
5. Bump the version only when releasing — see `.agents/RELEASE.md`.

## Tooling rules for agents

- Use plain, portable tooling: the npm/composer scripts above, `git`, and standard file edits.
  Do not rely on harness-specific "superpowers", private helpers, or hidden state that another
  agent (Codex, Claude, Copilot, Gemini, a human) cannot reproduce. Whatever you do, the next
  person must be able to redo it from this repo alone.
- If you need a new workflow step, add it as an npm/composer script and document it here — not as
  a one-off command that only you know.
- No new dependencies without an entry in `.agents/DECISIONS.md`.
- Never commit secrets, `vendor/`, `node_modules/`, `build/` or zips.

## Code comment rules

- Comments explain **why**, never what the line already says.
- One short line where a rule is non-obvious (schema constraint, Yoast quirk, sanitization
  reason). No essays, no decorative banners, no changelogs in comments.
- Keep PHPDoc blocks that WPCS requires — short summary plus real `@param`/`@return` types.
- The authoritative explanation belongs in `.agents/` docs; code comments only point at it.
