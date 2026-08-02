<?php
/**
 * Plugin Name:       IsuDev Schema Extended
 * Plugin URI:        https://github.com/IsuDevelopment/isudev-schema-extender
 * Description:       Extends the Yoast SEO schema graph with configurable, page-level entities.
 * Version:           0.2.5
 * Requires at least: 6.9
 * Requires PHP:      8.4
 * Requires Plugins:  wordpress-seo
 * Author:            IsuDev
 * Author URI:        https://isudev.pl
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       isudev-schema-extended
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined( 'ABSPATH' ) || exit;

\define( 'ISUDEV_SCHEMA_EXTENDED_FILE', __FILE__ );
\define( 'ISUDEV_SCHEMA_EXTENDED_VERSION', '0.2.5' );

$isudev_schema_extended_autoload = __DIR__ . '/vendor/autoload.php';

if ( \is_readable( $isudev_schema_extended_autoload ) ) {
	require_once $isudev_schema_extended_autoload;
}

// Update checks only matter in wp-admin and during cron, so the front end stays untouched.
if ( \class_exists( PucFactory::class ) && ( \is_admin() || \wp_doing_cron() ) ) {
	$isudev_schema_extended_updater = PucFactory::buildUpdateChecker(
		'https://github.com/IsuDevelopment/isudev-schema-extender/',
		__FILE__,
		'isudev-schema-extended'
	);
	$isudev_schema_extended_updater->getVcsApi()->enableReleaseAssets();
}

require_once __DIR__ . '/includes/service/class-meta-fields.php';
require_once __DIR__ . '/includes/class-editor-sidebar.php';
require_once __DIR__ . '/includes/class-plugin.php';

Plugin::register();
