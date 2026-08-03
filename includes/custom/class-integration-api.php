<?php
/**
 * Public integration contract for custom schema configuration.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Custom;

use WP_Error;

/**
 * Gives external plugins a stable API without exposing storage details.
 */
final class Integration_API {
	public const CONTRACT_VERSION = '1.0';

	/**
	 * Read one post's custom schema configuration.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array{contract_version: string, enabled: bool, source: string, validation: array<string, mixed>}|WP_Error
	 */
	public static function get_configuration( int $post_id ): array|WP_Error {
		$error = self::authorize_post( $post_id );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		$source = Meta_Fields::get_source( $post_id );

		return [
			'contract_version' => self::CONTRACT_VERSION,
			'enabled'          => Meta_Fields::is_enabled( $post_id ),
			'source'           => $source,
			'validation'       => Graph_Parser::parse( $source ),
		];
	}

	/**
	 * Validate proposed JSON without writing it.
	 *
	 * @param string $source JSON source.
	 *
	 * @return array<string, mixed>
	 */
	public static function validate_source( string $source ): array {
		return Graph_Parser::parse( Meta_Fields::sanitize_source( $source ) );
	}

	/**
	 * Persist a validated configuration for an authorized post.
	 *
	 * @param int    $post_id Post ID.
	 * @param bool   $enabled Whether custom schema should render.
	 * @param string $source  JSON source.
	 *
	 * @return array{contract_version: string, enabled: bool, source: string, validation: array<string, mixed>}|WP_Error
	 */
	public static function update_configuration( int $post_id, bool $enabled, string $source ): array|WP_Error {
		$error = self::authorize_post( $post_id );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		$source     = Meta_Fields::sanitize_source( $source );
		$validation = Graph_Parser::parse( $source );

		if ( $enabled && ! $validation['valid'] ) {
			return new WP_Error(
				'isudev_schema_extended_invalid_custom_schema',
				\__( 'Enabled Custom Schema must pass validation before it can be saved through the integration API.', 'isudev-schema-extended' ),
				[ 'validation' => $validation ]
			);
		}

		\update_post_meta( $post_id, Meta_Fields::SOURCE, $source );
		\update_post_meta( $post_id, Meta_Fields::ENABLED, $enabled );

		return self::get_configuration( $post_id );
	}

	/**
	 * Check post support and editor capability.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function authorize_post( int $post_id ): ?WP_Error {
		$post = \get_post( $post_id );
		if ( null === $post || ! \in_array( $post->post_type, Meta_Fields::get_supported_post_types(), true ) ) {
			return new WP_Error(
				'isudev_schema_extended_unsupported_post',
				\__( 'The requested post does not support Custom Schema.', 'isudev-schema-extended' )
			);
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'isudev_schema_extended_forbidden',
				\__( 'You are not allowed to edit Custom Schema for this post.', 'isudev-schema-extended' )
			);
		}

		return null;
	}
}
