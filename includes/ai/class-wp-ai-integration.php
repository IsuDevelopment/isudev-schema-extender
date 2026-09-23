<?php
/**
 * Optional WordPress AI plugin integration.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Ai;

/**
 * Registers the suggestion feature with the WordPress AI plugin when it is active.
 *
 * The plugin's feature classes are experimental, so everything that touches them lives in
 * `class-wp-ai-feature.php`, loaded only after the base class is known to exist.
 */
final class Wp_Ai_Integration {
	private const BASE_CLASS = 'WordPress\\AI\\Abstracts\\Abstract_Feature';

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		\add_action( 'wpai_register_features', [ self::class, 'register_feature' ] );
	}

	/**
	 * Whether the WordPress AI plugin is loaded.
	 */
	public static function is_ai_plugin_active(): bool {
		return \class_exists( self::BASE_CLASS );
	}

	/**
	 * Add the feature to the WordPress AI registry.
	 *
	 * @param object $registry WordPress AI feature registry.
	 */
	public static function register_feature( object $registry ): void {
		if ( ! self::is_ai_plugin_active() || ! \method_exists( $registry, 'register_feature' ) ) {
			return;
		}

		require_once __DIR__ . '/class-wp-ai-feature.php';

		$registry->register_feature( new Wp_Ai_Feature() );
	}
}
