<?php
/**
 * Yoast graph registration for custom schema entities.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Custom;

use Yoast\WP\SEO\Context\Meta_Tags_Context;

/**
 * Adds one generic graph piece to Yoast's schema collector.
 */
final class Schema_Integration {
	/**
	 * Register Yoast schema filters.
	 */
	public static function register(): void {
		\add_filter( 'wpseo_schema_graph_pieces', [ self::class, 'add_custom_piece' ], 12, 2 );
	}

	/**
	 * Add the custom graph piece for supported post types.
	 *
	 * @param array<int, object> $pieces  Existing graph pieces.
	 * @param Meta_Tags_Context  $context Current Yoast context.
	 *
	 * @return array<int, object>
	 */
	public static function add_custom_piece( array $pieces, Meta_Tags_Context $context ): array {
		if ( ! isset( $context->post->post_type )
			|| ! \in_array( $context->post->post_type, Meta_Fields::get_supported_post_types(), true )
		) {
			return $pieces;
		}

		$pieces[] = new Custom_Schema_Piece();

		return $pieces;
	}
}
