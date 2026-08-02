<?php
/**
 * Plugin bootstrap.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended;

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
		\add_action( 'init', [ Meta_Fields::class, 'register' ] );
		\add_action( 'enqueue_block_editor_assets', [ Editor_Sidebar::class, 'enqueue' ] );
		\add_action( 'plugins_loaded', [ self::class, 'register_yoast_integration' ], 20 );
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

		Schema_Integration::register();
	}
}
