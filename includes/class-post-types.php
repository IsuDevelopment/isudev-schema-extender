<?php
/**
 * Shared post type discovery and metadata support.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended;

use WP_Post_Type;

/**
 * Resolves content types that can expose Schema Extended controls.
 */
final class Post_Types {
	/**
	 * Get all public post types that WordPress considers front-end viewable.
	 *
	 * @return string[]
	 */
	public static function get_publicly_viewable(): array {
		$post_types = \get_post_types( [ 'public' => true ], 'objects' );

		return \array_values(
			\array_map(
				static fn( WP_Post_Type $post_type ): string => $post_type->name,
				\array_filter( $post_types, 'is_post_type_viewable' )
			)
		);
	}

	/**
	 * Enable the WordPress metadata contract required by the block editor REST API.
	 *
	 * @param string $post_type Registered post type name.
	 */
	public static function ensure_custom_fields_support( string $post_type ): void {
		if ( \post_type_exists( $post_type ) && ! \post_type_supports( $post_type, 'custom-fields' ) ) {
			\add_post_type_support( $post_type, 'custom-fields' );
		}
	}
}
