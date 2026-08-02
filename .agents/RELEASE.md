# Release

The plugin header is the single source of truth for the version. Everything else follows it.

## Steps

1. Bump `Version:` **and** `ISUDEV_SCHEMA_EXTENDED_VERSION` in `isudev-schema-extended.php`.
2. Mirror the same version in `composer.json` and `package.json`.
3. Add the release entry at the top of `CHANGELOG.md`.
4. Merge/push to `main`.

## What CI does (`.github/workflows/release.yml`)

Triggered by a push to `main` that touches `isudev-schema-extended.php` (or manually via
`workflow_dispatch`):

1. Reads the version from the plugin header.
2. Creates and pushes tag `v<version>` if it does not exist yet.
3. `composer install --no-dev` and `npm ci && npm run build`.
4. Packs `dist/isudev-schema-extended/` (runtime files + `vendor/` + `build/`, without `src/`,
   dev config and agent docs) into **`isudev-schema-extended-<version>.zip`**.
5. Publishes the GitHub Release `v<version>` with that zip as an asset.

The version is in the zip file name on purpose — the asset is self-describing when downloaded.

## Auto-update on client sites

Plugin Update Checker v5 polls the GitHub repo, reads the latest release and installs the release
asset. It boots only in wp-admin and cron. Requirements for it to keep working:

- the zip's top-level folder must stay `isudev-schema-extended` (the plugin slug);
- the release tag must stay `v<version>` matching the header;
- releases must carry the built zip as an asset (`enableReleaseAssets()`).

If the GitHub repository is renamed, update the URL in `isudev-schema-extended.php` and in
`README.md`.
