<?php
/**
 * Custom schema metadata registration.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Custom;

/**
 * Owns the persisted custom schema configuration.
 */
final class Meta_Fields {
	public const ENABLED = '_isudev_schema_custom_enabled';
	public const SOURCE  = '_isudev_schema_custom_json';

	public const MAX_SOURCE_LENGTH = 100000;

	/**
	 * Register private post meta for supported post types.
	 */
	public static function register(): void {
		foreach ( self::get_supported_post_types() as $post_type ) {
			self::register_field( $post_type, self::ENABLED, 'boolean', false, 'rest_sanitize_boolean' );
			self::register_field(
				$post_type,
				self::SOURCE,
				'string',
				'',
				[ self::class, 'sanitize_source' ],
				[
					'schema' => [
						'type'      => 'string',
						'default'   => '',
						'maxLength' => self::MAX_SOURCE_LENGTH,
					],
				]
			);
		}
	}

	/**
	 * Get post types that may contain custom schema entities.
	 *
	 * @return string[]
	 */
	public static function get_supported_post_types(): array {
		/**
		 * Filters post types supported by the Custom Schema feature.
		 *
		 * @param string[] $post_types Supported post type names.
		 */
		$post_types = \apply_filters( 'isudev_schema_extended_custom_post_types', [ 'page' ] );

		if ( ! \is_array( $post_types ) ) {
			return [ 'page' ];
		}

		return \array_values(
			\array_unique(
				\array_filter(
					\array_map( 'sanitize_key', $post_types ),
					static fn( string $post_type ): bool => '' !== $post_type
				)
			)
		);
	}

	/**
	 * Determine whether custom schema is enabled for a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function is_enabled( int $post_id ): bool {
		return true === \rest_sanitize_boolean( \get_post_meta( $post_id, self::ENABLED, true ) );
	}

	/**
	 * Get the stored JSON source.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function get_source( int $post_id ): string {
		$value = \get_post_meta( $post_id, self::SOURCE, true );

		return \is_string( $value ) ? $value : '';
	}

	/**
	 * Preserve editable JSON while removing invalid encoding and null bytes.
	 *
	 * Invalid JSON stays editable in the sidebar but is never rendered.
	 *
	 * @param mixed $value Potential JSON source.
	 */
	public static function sanitize_source( mixed $value ): string {
		if ( ! \is_string( $value ) ) {
			return '';
		}

		$value = \wp_check_invalid_utf8( $value );
		$value = \str_replace( "\0", '', $value );

		return \str_replace( [ "\r\n", "\r" ], "\n", $value );
	}

	/**
	 * Register one REST-exposed private post meta field.
	 *
	 * @param string                    $post_type        Supported post type.
	 * @param string                    $meta_key         Meta key to register.
	 * @param string                    $type             REST schema primitive type.
	 * @param mixed                     $default_value    Default metadata value.
	 * @param callable                  $sanitize_callback Input sanitizer.
	 * @param bool|array<string, mixed> $show_in_rest     REST exposure configuration.
	 */
	private static function register_field(
		string $post_type,
		string $meta_key,
		string $type,
		mixed $default_value,
		callable $sanitize_callback,
		bool|array $show_in_rest = true
	): void {
		\register_post_meta(
			$post_type,
			$meta_key,
			[
				'show_in_rest'      => $show_in_rest,
				'single'            => true,
				'type'              => $type,
				'default'           => $default_value,
				'sanitize_callback' => $sanitize_callback,
				'auth_callback'     => static function ( bool $allowed, string $key, int $post_id ): bool {
					unset( $allowed, $key );

					return \current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}
}
