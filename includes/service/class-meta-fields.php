<?php
/**
 * Service metadata registration and normalization.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Service;

/**
 * Owns the persisted service-page configuration.
 */
final class Meta_Fields {
	public const ENABLED      = '_isudev_yoast_service_enabled';
	public const NAME         = '_isudev_yoast_service_name';
	public const SERVICE_TYPE = '_isudev_yoast_service_type';
	public const DESCRIPTION  = '_isudev_yoast_service_description';
	public const AREAS        = '_isudev_yoast_service_areas';
	public const CATALOG_NAME = '_isudev_yoast_service_catalog_name';
	public const OFFERS       = '_isudev_yoast_service_offers';
	/**
	 * Legacy textarea metadata key.
	 *
	 * @deprecated Kept to read legacy text values without data loss.
	 */
	public const AREA_SERVED = '_isudev_yoast_service_area_served';
	public const BRANDS      = '_isudev_yoast_service_brands';

	private const AREA_TYPES = [ 'City', 'AdministrativeArea', 'Country' ];

	/**
	 * Register private post meta for supported post types.
	 */
	public static function register(): void {
		foreach ( self::get_supported_post_types() as $post_type ) {
			self::register_field( $post_type, self::ENABLED, 'boolean', false, 'rest_sanitize_boolean' );
			self::register_field( $post_type, self::NAME, 'string', '', 'sanitize_text_field' );
			self::register_field( $post_type, self::SERVICE_TYPE, 'string', '', 'sanitize_text_field' );
			self::register_field( $post_type, self::DESCRIPTION, 'string', '', 'sanitize_textarea_field' );
			self::register_field(
				$post_type,
				self::AREAS,
				'array',
				[],
				[ self::class, 'sanitize_areas' ],
				[
					'schema' => [
						'type'    => 'array',
						'default' => [],
						'items'   => [
							'type'                 => 'object',
							'properties'           => [
								'type' => [
									'type' => 'string',
									'enum' => self::AREA_TYPES,
								],
								'name' => [
									'type' => 'string',
								],
							],
							'required'             => [ 'type', 'name' ],
							'additionalProperties' => false,
						],
					],
				]
			);
			self::register_field( $post_type, self::AREA_SERVED, 'string', '', 'sanitize_textarea_field' );
			self::register_field( $post_type, self::BRANDS, 'string', '', 'sanitize_textarea_field' );
			self::register_field( $post_type, self::CATALOG_NAME, 'string', '', 'sanitize_text_field' );
			self::register_field(
				$post_type,
				self::OFFERS,
				'array',
				[],
				[ self::class, 'sanitize_offers' ],
				[
					'schema' => [
						'type'    => 'array',
						'default' => [],
						'items'   => [
							'type'                 => 'object',
							'properties'           => [
								'name'        => [
									'type' => 'string',
								],
								'description' => [
									'type' => 'string',
								],
							],
							'required'             => [ 'name' ],
							'additionalProperties' => false,
						],
					],
				]
			);
		}
	}

	/**
	 * Get post types that may describe a service.
	 *
	 * @return string[]
	 */
	public static function get_supported_post_types(): array {
		/**
		 * Legacy filter for post types supported by the Service feature.
		 *
		 * @param string[] $post_types Supported post type names.
		 */
		$post_types = \apply_filters( 'isudev_yoast_services_post_types', [ 'page' ] );

		/**
		 * Filters post types supported by the Schema Extended Service feature.
		 *
		 * @param string[] $post_types Supported post type names.
		 */
		$post_types = \apply_filters( 'isudev_schema_extended_service_post_types', $post_types );

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
	 * Determine whether a service entity is enabled for a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function is_enabled( int $post_id ): bool {
		return true === \rest_sanitize_boolean( \get_post_meta( $post_id, self::ENABLED, true ) );
	}

	/**
	 * Get a sanitized string metadata value.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Registered service meta key.
	 */
	public static function get_string( int $post_id, string $meta_key ): string {
		$value = \get_post_meta( $post_id, $meta_key, true );

		return \is_string( $value ) ? \trim( $value ) : '';
	}

	/**
	 * Convert a newline/comma-separated metadata value to unique entries.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Registered service meta key.
	 *
	 * @return string[]
	 */
	public static function get_list( int $post_id, string $meta_key ): array {
		$value = self::get_string( $post_id, $meta_key );

		if ( '' === $value ) {
			return [];
		}

		$items = \preg_split( '/[\r\n,]+/u', $value ) ?: [];
		$items = \array_map( 'sanitize_text_field', $items );
		$items = \array_filter( \array_map( 'trim', $items ), 'strlen' );

		return \array_values( \array_unique( $items ) );
	}

	/**
	 * Get validated, typed service areas.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<int, array{type: string, name: string}>
	 */
	public static function get_areas( int $post_id ): array {
		return self::sanitize_areas( \get_post_meta( $post_id, self::AREAS, true ) );
	}

	/**
	 * Validate and sanitize typed service areas received from REST.
	 *
	 * @param mixed $value Potential list of typed names.
	 *
	 * @return array<int, array{type: string, name: string}>
	 */
	public static function sanitize_areas( mixed $value ): array {
		if ( ! \is_array( $value ) ) {
			return [];
		}

		$areas = [];
		$seen  = [];

		foreach ( \array_slice( $value, 0, 100 ) as $area ) {
			if ( ! \is_array( $area ) || ! isset( $area['type'], $area['name'] ) ) {
				continue;
			}

			$type = \is_string( $area['type'] ) ? $area['type'] : '';
			$name = \is_string( $area['name'] ) ? \sanitize_text_field( $area['name'] ) : '';

			if ( ! \in_array( $type, self::AREA_TYPES, true ) || '' === $name ) {
				continue;
			}

			$identity = $type . "\0" . $name;
			if ( isset( $seen[ $identity ] ) ) {
				continue;
			}

			$seen[ $identity ] = true;
			$areas[]           = [
				'type' => $type,
				'name' => $name,
			];
		}

		return $areas;
	}

	/**
	 * Get validated catalog offers.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<int, array{name: string, description: string}>
	 */
	public static function get_offers( int $post_id ): array {
		return self::sanitize_offers( \get_post_meta( $post_id, self::OFFERS, true ) );
	}

	/**
	 * Validate and sanitize catalog offers received from REST.
	 *
	 * Empty names are discarded because an Offer without an identifiable
	 * item would not describe user-visible content meaningfully.
	 *
	 * @param mixed $value Potential list of offer definitions.
	 *
	 * @return array<int, array{name: string, description: string}>
	 */
	public static function sanitize_offers( mixed $value ): array {
		if ( ! \is_array( $value ) ) {
			return [];
		}

		$offers = [];
		$seen   = [];

		foreach ( \array_slice( $value, 0, 20 ) as $offer ) {
			if ( ! \is_array( $offer ) || ! isset( $offer['name'] ) || ! \is_string( $offer['name'] ) ) {
				continue;
			}

			$name = \sanitize_text_field( $offer['name'] );
			if ( '' === $name || isset( $seen[ $name ] ) ) {
				continue;
			}

			$description = isset( $offer['description'] ) && \is_string( $offer['description'] )
				? \sanitize_textarea_field( $offer['description'] )
				: '';

			$seen[ $name ] = true;
			$offers[]      = [
				'name'        => $name,
				'description' => $description,
			];
		}

		return $offers;
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
