<?php
/**
 * WordPress AI plugin feature definition.
 *
 * @package IsuDev\SchemaExtended
 */

declare( strict_types = 1 );

namespace IsuDev\SchemaExtended\Ai;

use WordPress\AI\Abstracts\Abstract_Feature;

/**
 * Exposes the suggestion toggle on the WordPress AI settings screen.
 *
 * The ability and editor button stay registered by this plugin; the AI plugin only decides
 * whether the feature is on.
 */
final class Wp_Ai_Feature extends Abstract_Feature {
	/**
	 * Feature identifier.
	 */
	public static function get_id(): string {
		return 'isudev-schema-suggestions';
	}

	/**
	 * Feature metadata.
	 *
	 * @return array{label: string, description: string, category: string}
	 */
	protected function load_metadata(): array {
		$category = 'WordPress\\AI\\Experiments\\Experiment_Category::EDITOR';

		return [
			'label'       => \__( 'Custom Schema suggestions', 'isudev-schema-extended' ),
			'description' => \__( 'Adds an "Explore & extend schema" button to the Schema Extended sidebar that proposes Schema.org nodes missing from the Yoast graph. Requires an AI connector with text generation.', 'isudev-schema-extended' ),
			'category'    => \defined( $category ) ? (string) \constant( $category ) : 'other',
		];
	}

	/**
	 * Called by the AI plugin only when the feature is enabled.
	 */
	public function register(): void {
		Schema_Suggester::mark_enabled_by_ai_plugin();
	}
}
