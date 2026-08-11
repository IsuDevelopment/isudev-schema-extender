<?php
/**
 * Plugin Name:       IsuDev Schema Extended
 * Plugin URI:        https://github.com/IsuDevelopment/isudev-schema-extender
 * Description:       Extends the Yoast SEO schema graph with configurable, content-level entities.
 * Version:           0.3.2
 * Requires at least: 6.9
 * Requires PHP:      8.4
 * Requires Plugins:  wordpress-seo
 * Author:            IsuDev
 * Author URI:        https://isudev.pl
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       isudev-schema-extended
 * Domain Path:       /languages
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined( 'ABSPATH' ) || exit;

\define( 'ISUDEV_SCHEMA_EXTENDED_FILE', __FILE__ );
\define( 'ISUDEV_SCHEMA_EXTENDED_VERSION', '0.3.2' );

$isudev_schema_extended_autoload = __DIR__ . '/vendor/autoload.php';

// A bundled vendor/ means this is a release-zip install. Composer-managed sites have none, and
// there Composer — not the update checker — owns the version.
$isudev_schema_extended_self_updates = \is_readable( $isudev_schema_extended_autoload );

if ( $isudev_schema_extended_self_updates ) {
	require_once $isudev_schema_extended_autoload;
}

// Update checks only matter in wp-admin and during cron, so the front end stays untouched.
if ( $isudev_schema_extended_self_updates && \class_exists( PucFactory::class ) && ( \is_admin() || \wp_doing_cron() ) ) {
	$isudev_schema_extended_updater = PucFactory::buildUpdateChecker(
		'https://github.com/IsuDevelopment/isudev-schema-extender/',
		__FILE__,
		'isudev-schema-extended'
	);
	$isudev_schema_extended_vcs_api = $isudev_schema_extended_updater->getVcsApi();

	// The built zip is a release asset, not the GitHub source archive. Checked by method instead of
	// class, because PUC exposes its API classes under a version-specific namespace.
	if ( \method_exists( $isudev_schema_extended_vcs_api, 'enableReleaseAssets' ) ) {
		$isudev_schema_extended_vcs_api->enableReleaseAssets();
	}
}

require_once __DIR__ . '/includes/class-post-types.php';
require_once __DIR__ . '/includes/service/class-meta-fields.php';
require_once __DIR__ . '/includes/custom/class-meta-fields.php';
require_once __DIR__ . '/includes/custom/class-graph-parser.php';
require_once __DIR__ . '/includes/custom/class-integration-api.php';
require_once __DIR__ . '/includes/class-editor-sidebar.php';
require_once __DIR__ . '/includes/class-plugin.php';

Plugin::register();
