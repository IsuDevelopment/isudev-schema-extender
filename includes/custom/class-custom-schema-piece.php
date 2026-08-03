<?php
/**
 * Yoast custom schema graph piece.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Custom;

use IsuDev\SchemaExtended\Service\Service_Schema_Piece;
use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Generates validated custom entities from the current page configuration.
 */
final class Custom_Schema_Piece extends Abstract_Schema_Piece {
	/**
	 * Keep custom nodes in one collector without restricting their Schema.org types.
	 *
	 * @var string
	 */
	public $identifier = 'isudev_custom_schema';

	/**
	 * Cached validation result for one graph build.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $validation = null;

	/**
	 * Determine whether the current context has valid, enabled custom nodes.
	 */
	public function is_needed(): bool {
		if ( ! isset( $this->context->post->ID, $this->context->post->post_type )
			|| ! \in_array( $this->context->post->post_type, Meta_Fields::get_supported_post_types(), true )
			|| \post_password_required( $this->context->post )
			|| ! Meta_Fields::is_enabled( (int) $this->context->post->ID )
		) {
			return false;
		}

		$this->validation = Graph_Parser::parse(
			Meta_Fields::get_source( (int) $this->context->post->ID ),
			$this->get_placeholders(),
			$this->get_reserved_ids()
		);

		return $this->validation['valid'] && [] !== $this->validation['nodes'];
	}

	/**
	 * Generate all validated custom graph nodes.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function generate(): array {
		if ( null === $this->validation ) {
			$this->is_needed();
		}

		return $this->validation['valid'] ? $this->validation['nodes'] : [];
	}

	/**
	 * Map portable editor placeholders to the current Yoast context.
	 *
	 * @return array<string, string>
	 */
	private function get_placeholders(): array {
		$canonical = (string) $this->context->canonical;
		$site_url  = (string) $this->context->site_url;

		return [
			'{{canonical}}'        => $canonical,
			'{{webpage_id}}'       => (string) $this->context->main_schema_id,
			'{{site_url}}'         => $site_url,
			'{{website_id}}'       => $site_url . Schema_IDs::WEBSITE_HASH,
			'{{organization_id}}'  => $site_url . Schema_IDs::ORGANIZATION_HASH,
			'{{primary_image_id}}' => $canonical . Schema_IDs::PRIMARY_IMAGE_HASH,
		];
	}

	/**
	 * Prevent custom nodes from replacing the primary entities owned by Yoast.
	 *
	 * @return string[]
	 */
	private function get_reserved_ids(): array {
		$canonical = (string) $this->context->canonical;
		$site_url  = (string) $this->context->site_url;

		return [
			(string) $this->context->main_schema_id,
			$site_url . Schema_IDs::WEBSITE_HASH,
			$site_url . Schema_IDs::ORGANIZATION_HASH,
			$site_url . Schema_IDs::ORGANIZATION_LOGO_HASH,
			$site_url . Schema_IDs::PERSON_HASH,
			$canonical . Schema_IDs::ARTICLE_HASH,
			$canonical . Schema_IDs::PRIMARY_IMAGE_HASH,
			$canonical . Schema_IDs::BREADCRUMB_HASH,
			Service_Schema_Piece::get_schema_id( $canonical ),
		];
	}
}
