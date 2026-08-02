<?php
/**
 * Yoast graph registration and WebPage linking.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Service;

use Yoast\WP\SEO\Context\Meta_Tags_Context;

/**
 * Connects the custom Service entity to Yoast's graph.
 */
final class Schema_Integration {
	/**
	 * Register Yoast schema filters.
	 */
	public static function register(): void {
		\add_filter( 'wpseo_schema_graph_pieces', [ self::class, 'add_service_piece' ], 11, 2 );
		\add_filter( 'wpseo_schema_webpage', [ self::class, 'link_webpage_to_service' ], 11, 2 );
	}

	/**
	 * Add the custom graph piece to Yoast's collector.
	 *
	 * @param array<int, object> $pieces  Existing graph pieces.
	 * @param Meta_Tags_Context  $context Current Yoast context.
	 *
	 * @return array<int, object>
	 */
	public static function add_service_piece( array $pieces, Meta_Tags_Context $context ): array {
		if ( ! isset( $context->post->post_type )
			|| ! \in_array( $context->post->post_type, Meta_Fields::get_supported_post_types(), true )
		) {
			return $pieces;
		}

		$pieces[] = new Service_Schema_Piece();

		return $pieces;
	}

	/**
	 * Add an `about` reference without disturbing Yoast FAQ `mainEntity` data.
	 *
	 * @param array<string, mixed> $data    Yoast WebPage graph node.
	 * @param Meta_Tags_Context    $context Current Yoast context.
	 *
	 * @return array<string, mixed>
	 */
	public static function link_webpage_to_service( array $data, Meta_Tags_Context $context ): array {
		if ( ! isset( $context->post->ID, $context->post->post_type )
			|| ! \in_array( $context->post->post_type, Meta_Fields::get_supported_post_types(), true )
			|| \post_password_required( $context->post )
			|| ! Meta_Fields::is_enabled( (int) $context->post->ID )
		) {
			return $data;
		}

		$reference = [
			'@id' => Service_Schema_Piece::get_schema_id( (string) $context->canonical ),
		];

		if ( empty( $data['about'] ) ) {
			$data['about'] = $reference;

			return $data;
		}

		if ( self::is_reference( $data['about'] ) ) {
			if ( $data['about']['@id'] !== $reference['@id'] ) {
				$data['about'] = [ $data['about'], $reference ];
			}

			return $data;
		}

		if ( \is_array( $data['about'] ) && ! self::contains_reference( $data['about'], $reference['@id'] ) ) {
			$data['about'][] = $reference;
		}

		return $data;
	}

	/**
	 * Determine whether a value is one Schema @id reference.
	 *
	 * @param mixed $value Potential Schema reference.
	 */
	private static function is_reference( mixed $value ): bool {
		return \is_array( $value ) && isset( $value['@id'] ) && \is_string( $value['@id'] );
	}

	/**
	 * Determine whether a list already contains a Schema @id reference.
	 *
	 * @param array<mixed> $references Existing about values.
	 * @param string       $schema_id  Schema entity identifier.
	 */
	private static function contains_reference( array $references, string $schema_id ): bool {
		foreach ( $references as $reference ) {
			if ( self::is_reference( $reference ) && $reference['@id'] === $schema_id ) {
				return true;
			}
		}

		return false;
	}
}
