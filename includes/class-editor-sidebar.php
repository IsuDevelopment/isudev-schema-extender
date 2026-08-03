<?php
/**
 * Block editor sidebar assets.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended;

use IsuDev\SchemaExtended\Custom\Meta_Fields as Custom_Meta_Fields;
use IsuDev\SchemaExtended\Service\Meta_Fields;
use WP_Screen;

/**
 * Loads the Service controls only in supported block editors.
 */
final class Editor_Sidebar {
	private const HANDLE = 'isudev-schema-extended-editor';

	/**
	 * Enqueue the compiled editor application.
	 */
	public static function enqueue(): void {
		$screen = \get_current_screen();

		$supported_post_types = \array_unique(
			\array_merge( Meta_Fields::get_supported_post_types(), Custom_Meta_Fields::get_supported_post_types() )
		);

		if ( ! $screen instanceof WP_Screen || ! \in_array( $screen->post_type, $supported_post_types, true ) ) {
			return;
		}

		$asset_file  = __DIR__ . '/../build/index.asset.php';
		$script_file = __DIR__ . '/../build/index.js';

		if ( ! \is_readable( $asset_file ) || ! \is_readable( $script_file ) ) {
			return;
		}

		$asset = require $asset_file;

		if ( ! \is_array( $asset ) || ! isset( $asset['dependencies'], $asset['version'] ) ) {
			return;
		}

		\wp_enqueue_script(
			self::HANDLE,
			\plugins_url( 'build/index.js', ISUDEV_SCHEMA_EXTENDED_FILE ),
			\array_values(
				\array_unique(
					\array_merge(
						$asset['dependencies'],
						[ 'wp-components', 'wp-data', 'wp-editor', 'wp-i18n', 'wp-plugins' ]
					)
				)
			),
			(string) $asset['version'],
			true
		);

		\wp_set_script_translations( self::HANDLE, 'isudev-schema-extended' );

		$style_file = __DIR__ . '/../build/index.css';
		if ( \is_readable( $style_file ) ) {
			\wp_enqueue_style(
				self::HANDLE,
				\plugins_url( 'build/index.css', ISUDEV_SCHEMA_EXTENDED_FILE ),
				[ 'wp-components' ],
				(string) $asset['version']
			);
			\wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
		}
	}
}
