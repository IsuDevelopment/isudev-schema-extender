<?php
/**
 * Custom schema JSON parsing and validation.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Custom;

use JsonException;

/**
 * Converts editor JSON into bounded Schema.org graph nodes.
 */
final class Graph_Parser {
	public const MAX_NODES              = 20;
	public const MAX_DEPTH              = 16;
	public const SUPPORTED_PLACEHOLDERS = [
		'{{canonical}}',
		'{{webpage_id}}',
		'{{site_url}}',
		'{{website_id}}',
		'{{organization_id}}',
		'{{primary_image_id}}',
	];

	/**
	 * Parse and validate custom schema source.
	 *
	 * @param string                $source       JSON source.
	 * @param array<string, string> $placeholders Context placeholders.
	 * @param string[]              $reserved_ids Yoast-owned top-level identifiers.
	 *
	 * @return array{valid: bool, nodes: array<int, array<string, mixed>>, errors: array<int, array{code: string, message: string}>, warnings: array<int, array{code: string, message: string}>}
	 */
	public static function parse( string $source, array $placeholders = [], array $reserved_ids = [] ): array {
		$errors   = [];
		$warnings = [];
		$source   = \trim( $source );

		if ( '' === $source ) {
			return self::result(
				[],
				[ self::diagnostic( 'empty_source', \__( 'Custom Schema JSON is empty.', 'isudev-schema-extended' ) ) ],
				[]
			);
		}

		if ( \strlen( $source ) > Meta_Fields::MAX_SOURCE_LENGTH ) {
			return self::result(
				[],
				[ self::diagnostic( 'source_too_large', \__( 'Custom Schema JSON exceeds the allowed size.', 'isudev-schema-extended' ) ) ],
				[]
			);
		}

		try {
			$decoded = \json_decode( $source, true, self::MAX_DEPTH, \JSON_THROW_ON_ERROR );
		} catch ( JsonException $exception ) {
			return self::result(
				[],
				[ self::diagnostic( 'invalid_json', $exception->getMessage() ) ],
				[]
			);
		}

		if ( ! \is_array( $decoded ) ) {
			return self::result(
				[],
				[ self::diagnostic( 'invalid_root', \__( 'Custom Schema must contain a JSON object, a node array or an @graph object.', 'isudev-schema-extended' ) ) ],
				[]
			);
		}

		$nodes = self::extract_nodes( $decoded, $errors, $warnings );
		if ( \count( $nodes ) > self::MAX_NODES ) {
			$errors[] = self::diagnostic(
				'too_many_nodes',
				\sprintf(
					/* translators: %d: maximum number of custom graph nodes. */
					\__( 'Custom Schema may contain at most %d graph nodes.', 'isudev-schema-extended' ),
					self::MAX_NODES
				)
			);
		}

		$normalized   = [];
		$seen_ids     = [];
		$canonical    = $placeholders['{{canonical}}'] ?? '';
		$reserved_ids = \array_fill_keys( $reserved_ids, true );

		foreach ( $nodes as $index => $node ) {
			if ( ! \is_array( $node ) || \array_is_list( $node ) ) {
				$errors[] = self::diagnostic(
					'invalid_node',
					self::node_message( $index, \__( 'must be a JSON object.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			if ( self::contains_key( $node, '@context' ) ) {
				$errors[] = self::diagnostic(
					'nested_context',
					self::node_message( $index, \__( 'must not define @context; Yoast owns the graph context.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			$types = self::normalize_types( $node['@type'] ?? null );
			if ( [] === $types ) {
				$errors[] = self::diagnostic(
					'missing_type',
					self::node_message( $index, \__( 'requires a non-empty @type.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			$node['@type'] = 1 === \count( $types ) ? $types[0] : $types;
			$node          = self::replace_placeholders( $node, $placeholders );
			if ( self::contains_unknown_placeholder( $node ) ) {
				$errors[] = self::diagnostic(
					'unknown_placeholder',
					self::node_message( $index, \__( 'contains an unknown context placeholder.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			if ( ! isset( $node['@id'] ) && '' !== $canonical ) {
				$node['@id'] = $canonical . '#custom-schema-' . ( $index + 1 );
			} elseif ( isset( $node['@id'] ) && \is_string( $node['@id'] ) ) {
				$node['@id'] = \trim( $node['@id'] );
				if ( '' !== $canonical && \str_starts_with( $node['@id'], '#' ) ) {
					$node['@id'] = $canonical . $node['@id'];
				}
			} elseif ( isset( $node['@id'] ) ) {
				$errors[] = self::diagnostic(
					'invalid_id',
					self::node_message( $index, \__( '@id must be a string.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			if ( isset( $node['@id'] ) && '' === $node['@id'] ) {
				$errors[] = self::diagnostic(
					'invalid_id',
					self::node_message( $index, \__( '@id must not be empty.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			if ( isset( $node['@id'] ) && isset( $reserved_ids[ $node['@id'] ] ) ) {
				$errors[] = self::diagnostic(
					'reserved_id',
					self::node_message( $index, \__( 'uses an @id owned by Yoast.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			if ( isset( $node['@id'] ) && isset( $seen_ids[ $node['@id'] ] ) ) {
				$errors[] = self::diagnostic(
					'duplicate_id',
					self::node_message( $index, \__( 'duplicates another custom node @id.', 'isudev-schema-extended' ) )
				);
				continue;
			}

			if ( isset( $node['@id'] ) ) {
				$seen_ids[ $node['@id'] ] = true;
			}

			$normalized[] = $node;
		}

		return self::result( $normalized, $errors, $warnings );
	}

	/**
	 * Extract graph nodes from the supported input shapes.
	 *
	 * @param array<mixed>                                     $decoded  Decoded JSON.
	 * @param array<int, array{code: string, message: string}> $errors   Validation errors.
	 * @param array<int, array{code: string, message: string}> $warnings Validation warnings.
	 *
	 * @return array<mixed>
	 */
	private static function extract_nodes( array $decoded, array &$errors, array &$warnings ): array {
		if ( \array_is_list( $decoded ) ) {
			return $decoded;
		}

		if ( isset( $decoded['@context'] ) ) {
			$context = $decoded['@context'];
			if ( ! \is_string( $context ) || ! \in_array( \untrailingslashit( $context ), [ 'https://schema.org', 'http://schema.org' ], true ) ) {
				$errors[] = self::diagnostic(
					'invalid_context',
					\__( 'Only the Schema.org @context may be supplied; Yoast will output it.', 'isudev-schema-extended' )
				);
			}
		}

		if ( \array_key_exists( '@graph', $decoded ) ) {
			if ( ! \is_array( $decoded['@graph'] ) || ! \array_is_list( $decoded['@graph'] ) ) {
				$errors[] = self::diagnostic(
					'invalid_graph',
					\__( '@graph must be an array of schema nodes.', 'isudev-schema-extended' )
				);

				return [];
			}

			$extra_keys = \array_diff( \array_keys( $decoded ), [ '@context', '@graph' ] );
			if ( [] !== $extra_keys ) {
				$warnings[] = self::diagnostic(
					'ignored_wrapper_properties',
					\__( 'Properties next to @graph are ignored.', 'isudev-schema-extended' )
				);
			}

			return $decoded['@graph'];
		}

		unset( $decoded['@context'] );

		return [ $decoded ];
	}

	/**
	 * Normalize a node's Schema.org types.
	 *
	 * @param mixed $value Potential @type value.
	 *
	 * @return string[]
	 */
	private static function normalize_types( mixed $value ): array {
		$types = \is_array( $value ) ? $value : [ $value ];
		$types = \array_filter(
			\array_map(
				static fn( mixed $type ): string => \is_string( $type ) ? \trim( $type ) : '',
				$types
			),
			'strlen'
		);

		return \array_values( \array_unique( $types ) );
	}

	/**
	 * Recursively replace context placeholders in string values.
	 *
	 * @param mixed                 $value        Schema value.
	 * @param array<string, string> $placeholders Context placeholders.
	 *
	 * @return mixed
	 */
	private static function replace_placeholders( mixed $value, array $placeholders ): mixed {
		if ( \is_string( $value ) ) {
			return \strtr( $value, $placeholders );
		}

		if ( ! \is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::replace_placeholders( $item, $placeholders );
		}

		return $value;
	}

	/**
	 * Detect unsupported context placeholders.
	 *
	 * @param mixed $value Schema value.
	 */
	private static function contains_unknown_placeholder( mixed $value ): bool {
		if ( \is_string( $value ) ) {
			$matches = [];
			\preg_match_all( '/\{\{[a-z_]+\}\}/', $value, $matches );

			return [] !== \array_diff( $matches[0], self::SUPPORTED_PLACEHOLDERS );
		}

		if ( ! \is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $item ) {
			if ( self::contains_unknown_placeholder( $item ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect a reserved key at any nesting level.
	 *
	 * @param array<mixed> $value  Schema node.
	 * @param string       $needle Key to locate.
	 */
	private static function contains_key( array $value, string $needle ): bool {
		foreach ( $value as $key => $item ) {
			if ( $key === $needle ) {
				return true;
			}

			if ( \is_array( $item ) && self::contains_key( $item, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Prefix a validation message with a one-based node number.
	 *
	 * @param int    $index   Zero-based node index.
	 * @param string $message Validation detail.
	 */
	private static function node_message( int $index, string $message ): string {
		return \sprintf(
			/* translators: 1: graph node number, 2: validation detail. */
			\__( 'Node %1$d %2$s', 'isudev-schema-extended' ),
			$index + 1,
			$message
		);
	}

	/**
	 * Build one diagnostic item.
	 *
	 * @param string $code    Stable machine-readable code.
	 * @param string $message Human-readable validation detail.
	 *
	 * @return array{code: string, message: string}
	 */
	private static function diagnostic( string $code, string $message ): array {
		return [
			'code'    => $code,
			'message' => $message,
		];
	}

	/**
	 * Build the public validation result.
	 *
	 * @param array<int, array<string, mixed>>                 $nodes    Normalized graph nodes.
	 * @param array<int, array{code: string, message: string}> $errors   Validation errors.
	 * @param array<int, array{code: string, message: string}> $warnings Validation warnings.
	 *
	 * @return array{valid: bool, nodes: array<int, array<string, mixed>>, errors: array<int, array{code: string, message: string}>, warnings: array<int, array{code: string, message: string}>}
	 */
	private static function result( array $nodes, array $errors, array $warnings ): array {
		return [
			'valid'    => [] === $errors,
			'nodes'    => $nodes,
			'errors'   => $errors,
			'warnings' => $warnings,
		];
	}
}
