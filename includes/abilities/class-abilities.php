<?php
/**
 * Abilities API adapter for Custom Schema.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Abilities;

use IsuDev\SchemaExtended\Ai\Schema_Suggester;
use IsuDev\SchemaExtended\Custom\Graph_Parser;
use IsuDev\SchemaExtended\Custom\Integration_API;
use IsuDev\SchemaExtended\Custom\Meta_Fields;
use WP_Error;

/**
 * Registers thin abilities over the public Custom Schema contract.
 */
final class Abilities {
	public const CATEGORY = 'isudev-schema';

	public const GET      = 'isudev-schema/get-custom-schema';
	public const VALIDATE = 'isudev-schema/validate-custom-schema';
	public const UPDATE   = 'isudev-schema/update-custom-schema';
	public const SUGGEST  = 'isudev-schema/suggest-custom-schema';

	/**
	 * Register Abilities API hooks.
	 */
	public static function register(): void {
		\add_action( 'wp_abilities_api_categories_init', [ self::class, 'register_category' ] );
		\add_action( 'wp_abilities_api_init', [ self::class, 'register_abilities' ] );
	}

	/**
	 * Register the ability category.
	 */
	public static function register_category(): void {
		\wp_register_ability_category(
			self::CATEGORY,
			[
				'label'       => \__( 'Schema Extended', 'isudev-schema-extended' ),
				'description' => \__( 'Read, validate, update and suggest Custom Schema nodes merged into the Yoast graph.', 'isudev-schema-extended' ),
			]
		);
	}

	/**
	 * Register all Custom Schema abilities.
	 */
	public static function register_abilities(): void {
		\wp_register_ability(
			self::GET,
			[
				'label'               => \__( 'Get Custom Schema', 'isudev-schema-extended' ),
				'description'         => \__( 'Returns the saved Custom Schema toggle, JSON source and structural validation for one post.', 'isudev-schema-extended' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(),
				'output_schema'       => self::configuration_schema(),
				'execute_callback'    => [ self::class, 'execute_get' ],
				'permission_callback' => [ self::class, 'can_edit_post' ],
				'meta'                => self::meta( self::GET, true, false, true ),
			]
		);

		\wp_register_ability(
			self::VALIDATE,
			[
				'label'               => \__( 'Validate Custom Schema', 'isudev-schema-extended' ),
				'description'         => \__( 'Validates proposed Custom Schema JSON for one post without saving it. Context placeholders stay unresolved.', 'isudev-schema-extended' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(
					[
						'source' => self::source_schema(),
					],
					[ 'source' ]
				),
				'output_schema'       => self::validation_schema(),
				'execute_callback'    => [ self::class, 'execute_validate' ],
				'permission_callback' => [ self::class, 'can_edit_post' ],
				'meta'                => self::meta( self::VALIDATE, true, false, true ),
			]
		);

		\wp_register_ability(
			self::UPDATE,
			[
				'label'               => \__( 'Update Custom Schema', 'isudev-schema-extended' ),
				'description'         => \__( 'Saves the Custom Schema toggle and/or JSON source for one post. Enabled JSON must pass validation. The source replaces the saved source as a whole.', 'isudev-schema-extended' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(
					[
						'enabled' => [
							'type'        => 'boolean',
							'description' => \__( 'Whether valid nodes are added to the Yoast graph. Omit to keep the saved value.', 'isudev-schema-extended' ),
						],
						'source'  => self::source_schema(),
					]
				),
				'output_schema'       => self::configuration_schema(),
				'execute_callback'    => [ self::class, 'execute_update' ],
				'permission_callback' => [ self::class, 'can_edit_post' ],
				// Destructive: the new source replaces nodes the caller may not have sent back.
				'meta'                => self::meta( self::UPDATE, false, true, false ),
			]
		);

		\wp_register_ability(
			self::SUGGEST,
			[
				'label'               => \__( 'Suggest Custom Schema', 'isudev-schema-extended' ),
				'description'         => \__( 'Uses the site AI connector to analyse the post and its Yoast graph, and proposes a complete Custom Schema source. Never saves anything.', 'isudev-schema-extended' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(
					[
						'content' => [
							'type'        => 'string',
							'maxLength'   => Schema_Suggester::MAX_INPUT_CONTENT_LENGTH,
							'description' => \__( 'Unsaved post content to analyse instead of the saved content.', 'isudev-schema-extended' ),
						],
						'source'  => self::source_schema(),
					]
				),
				'output_schema'       => self::suggestion_schema(),
				'execute_callback'    => [ self::class, 'execute_suggest' ],
				'permission_callback' => [ self::class, 'can_suggest' ],
				// Never writes, but not annotated readonly: core maps readonly to GET, and editor content
				// does not fit a query string. Not idempotent: the model answers differently each call.
				'meta'                => self::meta( self::SUGGEST, false, false, false ),
			]
		);
	}

	/**
	 * Allow only editors of the requested, supported post.
	 *
	 * @param mixed $input Ability input.
	 */
	public static function can_edit_post( mixed $input = null ): bool|WP_Error {
		$post_id = self::post_id( $input );
		// get_post( 0 ) would fall back to the global post.
		$post = $post_id > 0 ? \get_post( $post_id ) : null;

		if ( null === $post || ! \in_array( $post->post_type, Meta_Fields::get_supported_post_types(), true ) ) {
			return new WP_Error(
				'isudev_schema_extended_unsupported_post',
				\__( 'The requested post does not support Custom Schema.', 'isudev-schema-extended' ),
				[ 'status' => 404 ]
			);
		}

		return \current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Require an enabled AI suggestion feature on top of post access.
	 *
	 * @param mixed $input Ability input.
	 */
	public static function can_suggest( mixed $input = null ): bool|WP_Error {
		if ( ! Schema_Suggester::is_enabled() ) {
			return new WP_Error(
				'isudev_schema_extended_ai_disabled',
				\__( 'AI schema suggestions are disabled on this site.', 'isudev-schema-extended' ),
				[ 'status' => 403 ]
			);
		}

		return self::can_edit_post( $input );
	}

	/**
	 * Read one post's configuration.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_get( mixed $input = null ): array|WP_Error {
		return Integration_API::get_configuration( self::post_id( $input ) );
	}

	/**
	 * Validate a proposed source without writing.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array<string, mixed>
	 */
	public static function execute_validate( mixed $input = null ): array {
		$source = \is_array( $input ) && \is_string( $input['source'] ?? null ) ? $input['source'] : '';

		return Integration_API::validate_source( $source );
	}

	/**
	 * Merge omitted fields with saved values and persist the configuration.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_update( mixed $input = null ): array|WP_Error {
		$post_id = self::post_id( $input );
		$input   = \is_array( $input ) ? $input : [];

		if ( ! \array_key_exists( 'enabled', $input ) && ! \array_key_exists( 'source', $input ) ) {
			return new WP_Error(
				'isudev_schema_extended_empty_update',
				\__( 'Provide enabled, source or both.', 'isudev-schema-extended' ),
				[ 'status' => 400 ]
			);
		}

		$enabled = \array_key_exists( 'enabled', $input ) ? (bool) $input['enabled'] : Meta_Fields::is_enabled( $post_id );
		$source  = \is_string( $input['source'] ?? null ) ? $input['source'] : Meta_Fields::get_source( $post_id );

		return Integration_API::update_configuration( $post_id, $enabled, $source );
	}

	/**
	 * Ask the AI connector for a proposal.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_suggest( mixed $input = null ): array|WP_Error {
		$input = \is_array( $input ) ? $input : [];

		return Schema_Suggester::suggest(
			self::post_id( $input ),
			\is_string( $input['content'] ?? null ) ? $input['content'] : null,
			\is_string( $input['source'] ?? null ) ? $input['source'] : null
		);
	}

	/**
	 * Read the post ID from ability input.
	 *
	 * @param mixed $input Ability input.
	 */
	private static function post_id( mixed $input ): int {
		return \is_array( $input ) ? \absint( $input['post_id'] ?? 0 ) : 0;
	}

	/**
	 * Build ability metadata with annotations and MCP exposure.
	 *
	 * @param string $name        Ability name.
	 * @param bool   $read_only   Whether the ability never writes.
	 * @param bool   $destructive Whether the ability can drop data the caller did not send.
	 * @param bool   $idempotent  Whether repeated calls give the same result.
	 *
	 * @return array<string, mixed>
	 */
	private static function meta( string $name, bool $read_only, bool $destructive, bool $idempotent ): array {
		/**
		 * Filters whether a Schema Extended ability is exposed to MCP clients.
		 *
		 * Sites that also run WP Content Bridge can hide the CRUD abilities here so a client
		 * connected to both MCP servers does not see the same operation twice.
		 *
		 * @param bool   $public Whether the ability is exposed. Default true.
		 * @param string $name   Ability name.
		 */
		$public = (bool) \apply_filters( 'isudev_schema_extended_mcp_public', true, $name );

		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => $read_only,
				'destructive' => $destructive,
				'idempotent'  => $idempotent,
			],
			'mcp'          => [
				'public' => $public,
				'type'   => 'tool',
			],
		];
	}

	/**
	 * Build an object input schema that always requires a post ID.
	 *
	 * @param array<string, mixed> $properties Additional properties.
	 * @param string[]             $required   Additional required properties.
	 *
	 * @return array<string, mixed>
	 */
	private static function input_schema( array $properties = [], array $required = [] ): array {
		return [
			'type'                 => 'object',
			'properties'           => \array_merge(
				[
					'post_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => \__( 'ID of a post that supports Custom Schema.', 'isudev-schema-extended' ),
					],
				],
				$properties
			),
			'required'             => \array_merge( [ 'post_id' ], $required ),
			'additionalProperties' => false,
		];
	}

	/**
	 * Schema of a Custom Schema JSON source.
	 *
	 * @return array<string, mixed>
	 */
	private static function source_schema(): array {
		return [
			'type'        => 'string',
			'maxLength'   => Meta_Fields::MAX_SOURCE_LENGTH,
			'description' => \sprintf(
				/* translators: %s: comma-separated list of supported placeholders. */
				\__( 'Schema.org JSON: one object, a node array or an @graph object, without nested @context. Supported placeholders: %s.', 'isudev-schema-extended' ),
				\implode( ', ', Graph_Parser::SUPPORTED_PLACEHOLDERS )
			),
		];
	}

	/**
	 * Schema of one Graph_Parser result.
	 *
	 * @return array<string, mixed>
	 */
	private static function validation_schema(): array {
		$diagnostics = [
			'type'  => 'array',
			'items' => [
				'type'       => 'object',
				'properties' => [
					'code'    => [ 'type' => 'string' ],
					'message' => [ 'type' => 'string' ],
				],
				'required'   => [ 'code', 'message' ],
			],
		];

		return [
			'type'       => 'object',
			'properties' => [
				'valid'    => [ 'type' => 'boolean' ],
				'nodes'    => [
					'type'  => 'array',
					'items' => [ 'type' => 'object' ],
				],
				'errors'   => $diagnostics,
				'warnings' => $diagnostics,
			],
			'required'   => [ 'valid', 'nodes', 'errors', 'warnings' ],
		];
	}

	/**
	 * Schema of a saved configuration.
	 *
	 * @return array<string, mixed>
	 */
	private static function configuration_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'contract_version' => [ 'type' => 'string' ],
				'enabled'          => [ 'type' => 'boolean' ],
				'source'           => [ 'type' => 'string' ],
				'validation'       => self::validation_schema(),
			],
			'required'   => [ 'contract_version', 'enabled', 'source', 'validation' ],
		];
	}

	/**
	 * Schema of an AI suggestion.
	 *
	 * @return array<string, mixed>
	 */
	private static function suggestion_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'analysis'         => [ 'type' => 'string' ],
				'gaps'             => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'title'  => [ 'type' => 'string' ],
							'reason' => [ 'type' => 'string' ],
						],
						'required'   => [ 'title', 'reason' ],
					],
				],
				'proposed_source'  => [ 'type' => 'string' ],
				'removed'          => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => \__( 'Types and properties stripped from the proposal because they need verifiable on-page evidence.', 'isudev-schema-extended' ),
				],
				'validation'       => self::validation_schema(),
				'writes_performed' => [
					'type'  => 'boolean',
					'const' => false,
				],
			],
			'required'   => [ 'analysis', 'gaps', 'proposed_source', 'removed', 'validation', 'writes_performed' ],
		];
	}
}
