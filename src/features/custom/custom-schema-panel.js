const { Button, Notice, PanelBody, TextareaControl, ToggleControl } = window.wp.components;
const { useDispatch, useSelect } = window.wp.data;
const { __, sprintf } = window.wp.i18n;

const META = {
	enabled: '_isudev_schema_custom_enabled',
	source: '_isudev_schema_custom_json',
};

const MAX_NODES = 20;
const MAX_SOURCE_LENGTH = 100000;
const SUPPORTED_PLACEHOLDERS = new Set( [
	'{{canonical}}',
	'{{webpage_id}}',
	'{{site_url}}',
	'{{website_id}}',
	'{{organization_id}}',
	'{{primary_image_id}}',
] );

const EXAMPLE = JSON.stringify(
	{
		'@type': 'VideoObject',
		'@id': '{{canonical}}#installation-video',
		name: __( 'Security system installation', 'isudev-schema-extended' ),
		mainEntityOfPage: {
			'@id': '{{webpage_id}}',
		},
	},
	null,
	2
);

const hasNestedContext = ( value ) => {
	if ( ! value || typeof value !== 'object' ) {
		return false;
	}

	return Object.entries( value ).some( ( [ key, child ] ) => key === '@context' || hasNestedContext( child ) );
};

const hasUnknownPlaceholder = ( value ) => {
	if ( typeof value === 'string' ) {
		const placeholders = value.match( /\{\{[a-z_]+\}\}/g ) ?? [];
		return placeholders.some( ( placeholder ) => ! SUPPORTED_PLACEHOLDERS.has( placeholder ) );
	}

	if ( ! value || typeof value !== 'object' ) {
		return false;
	}

	return Object.values( value ).some( hasUnknownPlaceholder );
};

const validateSource = ( source ) => {
	if ( typeof source !== 'string' || source.trim() === '' ) {
		return {
			status: 'info',
			message: __( 'Paste Schema.org JSON to get started.', 'isudev-schema-extended' ),
		};
	}

	if ( source.length > MAX_SOURCE_LENGTH ) {
		return {
			status: 'error',
			message: __( 'The JSON exceeds the allowed size.', 'isudev-schema-extended' ),
		};
	}

	let decoded;
	try {
		decoded = JSON.parse( source );
	} catch ( error ) {
		return {
			status: 'error',
			message: sprintf(
				/* translators: %s: JSON parser error. */
				__( 'Invalid JSON: %s', 'isudev-schema-extended' ),
				error.message
			),
		};
	}

	if ( ! decoded || typeof decoded !== 'object' ) {
		return {
			status: 'error',
			message: __(
				'The root value must be an object, a node array or an @graph object.',
				'isudev-schema-extended'
			),
		};
	}

	let nodes;
	let allowRootContext = false;
	if ( Array.isArray( decoded ) ) {
		nodes = decoded;
	} else if ( Object.prototype.hasOwnProperty.call( decoded, '@graph' ) ) {
		const context = decoded[ '@context' ];
		if (
			context !== undefined &&
			context !== 'https://schema.org' &&
			context !== 'https://schema.org/' &&
			context !== 'http://schema.org' &&
			context !== 'http://schema.org/'
		) {
			return {
				status: 'error',
				message: __( 'Only the Schema.org context is allowed.', 'isudev-schema-extended' ),
			};
		}

		nodes = decoded[ '@graph' ];
	} else {
		allowRootContext = true;
		const context = decoded[ '@context' ];
		if (
			context !== undefined &&
			context !== 'https://schema.org' &&
			context !== 'https://schema.org/' &&
			context !== 'http://schema.org' &&
			context !== 'http://schema.org/'
		) {
			return {
				status: 'error',
				message: __( 'Only the Schema.org context is allowed.', 'isudev-schema-extended' ),
			};
		}

		nodes = [ decoded ];
	}

	if ( ! Array.isArray( nodes ) || nodes.length === 0 ) {
		return {
			status: 'error',
			message: __( '@graph must contain at least one node.', 'isudev-schema-extended' ),
		};
	}

	if ( nodes.length > MAX_NODES ) {
		return {
			status: 'error',
			message: sprintf(
				/* translators: %d: maximum number of custom graph nodes. */
				__( 'You may add at most %d nodes.', 'isudev-schema-extended' ),
				MAX_NODES
			),
		};
	}

	const identifiers = new Set();
	for ( let index = 0; index < nodes.length; index += 1 ) {
		const node = nodes[ index ];
		if ( ! node || typeof node !== 'object' || Array.isArray( node ) ) {
			return {
				status: 'error',
				message: sprintf(
					/* translators: %d: one-based graph node number. */
					__( 'Node %d must be a JSON object.', 'isudev-schema-extended' ),
					index + 1
				),
			};
		}

		const nodeEntries = Object.entries( node ).filter(
			( [ key ] ) => ! ( allowRootContext && key === '@context' )
		);
		if ( nodeEntries.some( ( [ key, child ] ) => key === '@context' || hasNestedContext( child ) ) ) {
			return {
				status: 'error',
				message: sprintf(
					/* translators: %d: one-based graph node number. */
					__( 'Node %d contains a nested @context.', 'isudev-schema-extended' ),
					index + 1
				),
			};
		}

		if ( hasUnknownPlaceholder( node ) ) {
			return {
				status: 'error',
				message: sprintf(
					/* translators: %d: one-based graph node number. */
					__( 'Node %d contains an unknown placeholder.', 'isudev-schema-extended' ),
					index + 1
				),
			};
		}

		const type = node[ '@type' ];
		const validType =
			( typeof type === 'string' && type.trim() !== '' ) ||
			( Array.isArray( type ) &&
				type.length > 0 &&
				type.every( ( item ) => typeof item === 'string' && item.trim() !== '' ) );
		if ( ! validType ) {
			return {
				status: 'error',
				message: sprintf(
					/* translators: %d: one-based graph node number. */
					__( 'Node %d requires a valid @type.', 'isudev-schema-extended' ),
					index + 1
				),
			};
		}

		if ( Object.prototype.hasOwnProperty.call( node, '@id' ) ) {
			if ( typeof node[ '@id' ] !== 'string' || node[ '@id' ].trim() === '' ) {
				return {
					status: 'error',
					message: sprintf(
						/* translators: %d: one-based graph node number. */
						__( 'Node %d has an invalid @id.', 'isudev-schema-extended' ),
						index + 1
					),
				};
			}

			if ( identifiers.has( node[ '@id' ] ) ) {
				return {
					status: 'error',
					message: __( 'Every node must have a unique @id.', 'isudev-schema-extended' ),
				};
			}
			identifiers.add( node[ '@id' ] );
		}
	}

	return {
		status: 'success',
		message: sprintf(
			/* translators: %d: number of valid custom graph nodes. */
			__( 'The JSON is valid. Recognized nodes: %d.', 'isudev-schema-extended' ),
			nodes.length
		),
	};
};

const CustomSchemaPanel = () => {
	const meta = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) ?? {}, [] );
	const { editPost } = useDispatch( 'core/editor' );
	const enabled = Boolean( meta[ META.enabled ] );
	const source = typeof meta[ META.source ] === 'string' ? meta[ META.source ] : '';
	const validation = validateSource( source );
	if ( enabled && validation.status === 'info' ) {
		validation.status = 'error';
		validation.message = __(
			'Custom Schema is enabled, but the JSON is empty. Nothing will be published.',
			'isudev-schema-extended'
		);
	}

	const updateMeta = ( key, value ) => {
		editPost( {
			meta: {
				...meta,
				[ key ]: value,
			},
		} );
	};

	return (
		<PanelBody title={ __( 'Custom Schema', 'isudev-schema-extended' ) } initialOpen={ false }>
			<div className="isudev-schema-extended__fields">
				<ToggleControl
					label={ __( 'Add custom nodes to the Yoast graph', 'isudev-schema-extended' ) }
					help={
						enabled
							? __( 'Valid nodes will be added to the Yoast graph.', 'isudev-schema-extended' )
							: __( 'The JSON remains saved but is not published.', 'isudev-schema-extended' )
					}
					checked={ enabled }
					onChange={ ( value ) => updateMeta( META.enabled, value ) }
				/>

				<Notice status={ validation.status } isDismissible={ false }>
					{ validation.message }
				</Notice>

				<TextareaControl
					className="isudev-custom-schema__source"
					label={ __( 'JSON Schema.org', 'isudev-schema-extended' ) }
					help={ __(
						'Paste a single object, a node array or a complete @graph object. Yoast provides @context.',
						'isudev-schema-extended'
					) }
					rows={ 18 }
					value={ source }
					placeholder={ EXAMPLE }
					onChange={ ( value ) => updateMeta( META.source, value ) }
				/>

				<p className="isudev-custom-schema__help">
					{ __(
						'Available placeholders: {{canonical}}, {{webpage_id}}, {{site_url}}, {{website_id}}, {{organization_id}}, {{primary_image_id}}.',
						'isudev-schema-extended'
					) }
				</p>

				<Button
					variant="secondary"
					isDestructive
					disabled={ source === '' }
					onClick={ () => updateMeta( META.source, '' ) }
				>
					{ __( 'Clear configuration', 'isudev-schema-extended' ) }
				</Button>
			</div>
		</PanelBody>
	);
};

export default CustomSchemaPanel;
