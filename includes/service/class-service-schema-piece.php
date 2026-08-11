<?php
/**
 * Yoast Service schema graph piece.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Service;

use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Generates one Service entity from the current page configuration.
 */
final class Service_Schema_Piece extends Abstract_Schema_Piece {
	/**
	 * Keep this piece distinct from integrations that may also use Service.
	 *
	 * @var string
	 */
	public $identifier = 'isudev_service';

	/**
	 * Determine whether the current Yoast context represents an enabled service.
	 */
	public function is_needed(): bool {
		return isset( $this->context->post->ID )
			&& \in_array( $this->context->post->post_type, Meta_Fields::get_supported_post_types(), true )
			&& ! \post_password_required( $this->context->post )
			&& Meta_Fields::is_enabled( (int) $this->context->post->ID );
	}

	/**
	 * Generate the Service graph node.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$post_id      = (int) $this->context->post->ID;
		$canonical    = (string) $this->context->canonical;
		$name         = Meta_Fields::get_string( $post_id, Meta_Fields::NAME );
		$service_type = Meta_Fields::get_string( $post_id, Meta_Fields::SERVICE_TYPE );
		$description  = Meta_Fields::get_string( $post_id, Meta_Fields::DESCRIPTION );

		if ( '' === $name ) {
			$name = \get_the_title( $post_id );
		}

		if ( '' === $service_type ) {
			$service_type = $name;
		}

		if ( '' === $description && \is_string( $this->context->description ) ) {
			$description = $this->context->description;
		}

		$name         = $this->helpers->schema->html->smart_strip_tags( $name );
		$service_type = $this->helpers->schema->html->smart_strip_tags( $service_type );
		$description  = $this->helpers->schema->html->smart_strip_tags( $description );

		$data = [
			'@type'            => 'Service',
			'@id'              => self::get_schema_id( $canonical ),
			'url'              => $canonical,
			'name'             => $name,
			'serviceType'      => $service_type,
			'provider'         => [
				'@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH,
			],
			'mainEntityOfPage' => [
				'@id' => $this->context->main_schema_id,
			],
		];

		if ( '' !== $description ) {
			$data['description'] = $description;
		}

		$areas = \array_map(
			static fn( array $area ): array => [
				'@type' => $area['type'],
				'name'  => $area['name'],
			],
			Meta_Fields::get_areas( $post_id )
		);

		// Preserve old textarea values as Schema.org Text until an editor assigns explicit types.
		if ( [] === $areas ) {
			$areas = Meta_Fields::get_list( $post_id, Meta_Fields::AREA_SERVED );
		}

		if ( [] !== $areas ) {
			$data['areaServed'] = $areas;
		}

		$offers = Meta_Fields::get_offers( $post_id );
		if ( [] !== $offers ) {
			$catalog_name = Meta_Fields::get_string( $post_id, Meta_Fields::CATALOG_NAME );
			if ( '' === $catalog_name ) {
				$catalog_name = \sprintf(
					/* translators: %s: service name. */
					\__( 'Service scope: %s', 'isudev-schema-extended' ),
					$name
				);
			}

			$catalog_name  = $this->helpers->schema->html->smart_strip_tags( $catalog_name );
			$catalog_items = [];

			foreach ( $offers as $offer ) {
				$offer_name        = $this->helpers->schema->html->smart_strip_tags( $offer['name'] );
				$offer_description = $this->helpers->schema->html->smart_strip_tags( $offer['description'] );
				$item_offered      = [
					'@type' => 'Service',
					'name'  => $offer_name,
				];

				if ( '' !== $offer_description ) {
					$item_offered['description'] = $offer_description;
				}

				$catalog_items[] = [
					'@type'       => 'Offer',
					'itemOffered' => $item_offered,
				];
			}

			$data['hasOfferCatalog'] = [
				'@type'           => 'OfferCatalog',
				'name'            => $catalog_name,
				'itemListElement' => $catalog_items,
			];
		}

		$brands = Meta_Fields::get_list( $post_id, Meta_Fields::BRANDS );
		if ( [] !== $brands ) {
			$data['brand'] = \array_map(
				static fn( string $brand ): array => [
					'@type' => 'Brand',
					'name'  => $brand,
				],
				$brands
			);
		}

		if ( $this->context->has_image ) {
			$data['image'] = [
				'@id' => $canonical . Schema_IDs::PRIMARY_IMAGE_HASH,
			];
		}

		return $this->helpers->schema->language->add_piece_language( $data );
	}

	/**
	 * Build the stable Service entity identifier for a canonical URL.
	 *
	 * @param string $canonical Canonical page URL.
	 */
	public static function get_schema_id( string $canonical ): string {
		return $canonical . '#service';
	}
}
