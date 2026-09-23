<?php
/**
 * Plugin bootstrap.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended;

use IsuDev\SchemaExtended\Abilities\Abilities;
use IsuDev\SchemaExtended\Ai\Wp_Ai_Integration;
use IsuDev\SchemaExtended\Custom\Meta_Fields as Custom_Meta_Fields;
use IsuDev\SchemaExtended\Custom\Schema_Integration as Custom_Schema_Integration;
use IsuDev\SchemaExtended\Service\Meta_Fields;
use IsuDev\SchemaExtended\Service\Schema_Integration;

/**
 * Coordinates editor metadata and the optional Yoast integration.
 */
final class Plugin {
	/**
	 * Register plugin hooks.
	 */
	public static function register(): void {
		\add_action( 'init', [ self::class, 'load_textdomain' ] );
		// Run after conventional CPT registration so dynamic discovery sees third-party types.
		\add_action( 'init', [ Meta_Fields::class, 'register' ], 100 );
		\add_action( 'init', [ Custom_Meta_Fields::class, 'register' ], 100 );
		\add_action( 'enqueue_block_editor_assets', [ Editor_Sidebar::class, 'enqueue' ] );
		Abilities::register();
		Wp_Ai_Integration::register();
		\add_action( 'plugins_loaded', [ self::class, 'register_yoast_integration' ], 20 );
	}

	/**
	 * Load bundled translations for installations outside WordPress.org.
	 */
	public static function load_textdomain(): void {
		\load_plugin_textdomain(
			'isudev-schema-extended',
			false,
			\dirname( \plugin_basename( ISUDEV_SCHEMA_EXTENDED_FILE ) ) . '/languages'
		);
	}

	/**
	 * Load classes that extend Yoast only after Yoast has loaded.
	 */
	public static function register_yoast_integration(): void {
		if ( ! \class_exists( 'Yoast\\WP\\SEO\\Generators\\Schema\\Abstract_Schema_Piece' ) ) {
			return;
		}

		require_once __DIR__ . '/service/class-service-schema-piece.php';
		require_once __DIR__ . '/service/class-schema-integration.php';
		require_once __DIR__ . '/custom/class-custom-schema-piece.php';
		require_once __DIR__ . '/custom/class-schema-integration.php';

		Schema_Integration::register();
		Custom_Schema_Integration::register();
	}
}
