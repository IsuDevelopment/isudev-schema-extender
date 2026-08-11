/**
 * Internal dependencies
 */
import TypedNameRepeater from '../../components/typed-name-repeater';
import OfferRepeater from '../../components/offer-repeater';

const { PanelBody, TextControl, TextareaControl, ToggleControl } = window.wp.components;
const { useDispatch, useSelect } = window.wp.data;
const { __ } = window.wp.i18n;

const META = {
	enabled: '_isudev_yoast_service_enabled',
	name: '_isudev_yoast_service_name',
	serviceType: '_isudev_yoast_service_type',
	description: '_isudev_yoast_service_description',
	areas: '_isudev_yoast_service_areas',
	legacyAreaServed: '_isudev_yoast_service_area_served',
	brands: '_isudev_yoast_service_brands',
	catalogName: '_isudev_yoast_service_catalog_name',
	offers: '_isudev_yoast_service_offers',
};

const AREA_TYPES = [
	{
		label: __( 'City', 'isudev-schema-extended' ),
		value: 'City',
	},
	{
		label: __( 'Administrative area', 'isudev-schema-extended' ),
		value: 'AdministrativeArea',
	},
	{
		label: __( 'Country', 'isudev-schema-extended' ),
		value: 'Country',
	},
];

const getLegacyAreas = ( value ) => {
	if ( typeof value !== 'string' || value.trim() === '' ) {
		return [];
	}

	return value
		.split( /[\r\n,]+/u )
		.map( ( name ) => name.trim() )
		.filter( Boolean )
		.map( ( name ) => ( {
			type: 'City',
			name,
		} ) );
};

const ServicePanel = () => {
	const meta = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) ?? {}, [] );
	const { editPost } = useDispatch( 'core/editor' );

	const updateMetaValues = ( values ) => {
		editPost( {
			meta: {
				...meta,
				...values,
			},
		} );
	};

	const updateMeta = ( key, value ) => updateMetaValues( { [ key ]: value } );

	const enabled = Boolean( meta[ META.enabled ] );
	const areas =
		Array.isArray( meta[ META.areas ] ) && meta[ META.areas ].length > 0
			? meta[ META.areas ]
			: getLegacyAreas( meta[ META.legacyAreaServed ] );

	return (
		<PanelBody title={ __( 'Service', 'isudev-schema-extended' ) } initialOpen>
			<ToggleControl
				label={ __( 'Add Service to schema', 'isudev-schema-extended' ) }
				help={ __( 'The service will be added to the existing Yoast SEO graph.', 'isudev-schema-extended' ) }
				checked={ enabled }
				onChange={ ( value ) => updateMeta( META.enabled, value ) }
			/>

			{ enabled && (
				<div className="isudev-schema-extended__fields">
					<TextControl
						label={ __( 'Service name', 'isudev-schema-extended' ) }
						help={ __( 'Leave empty to use the content title.', 'isudev-schema-extended' ) }
						value={ meta[ META.name ] ?? '' }
						onChange={ ( value ) => updateMeta( META.name, value ) }
					/>

					<TextControl
						label={ __( 'Service type', 'isudev-schema-extended' ) }
						help={ __( 'Example: Gate automation installation and servicing.', 'isudev-schema-extended' ) }
						value={ meta[ META.serviceType ] ?? '' }
						onChange={ ( value ) => updateMeta( META.serviceType, value ) }
					/>

					<TextareaControl
						label={ __( 'Service description', 'isudev-schema-extended' ) }
						help={ __( 'Leave empty to use the Yoast SEO description.', 'isudev-schema-extended' ) }
						value={ meta[ META.description ] ?? '' }
						onChange={ ( value ) => updateMeta( META.description, value ) }
					/>

					<TypedNameRepeater
						label={ __( 'Service areas', 'isudev-schema-extended' ) }
						help={ __(
							'Add each area separately and select its correct Schema.org type.',
							'isudev-schema-extended'
						) }
						value={ areas }
						typeOptions={ AREA_TYPES }
						typeLabel={ __( 'Area type', 'isudev-schema-extended' ) }
						nameLabel={ __( 'Area name', 'isudev-schema-extended' ) }
						addLabel={ __( 'Add area', 'isudev-schema-extended' ) }
						removeLabel={ __( 'Remove area', 'isudev-schema-extended' ) }
						onChange={ ( value ) =>
							updateMetaValues( {
								[ META.areas ]: value,
								[ META.legacyAreaServed ]: '',
							} )
						}
					/>

					<TextControl
						label={ __( 'Service catalog name', 'isudev-schema-extended' ) }
						help={ __(
							'Example: Security services in Wroclaw. Leave empty to derive the catalog name from the service name.',
							'isudev-schema-extended'
						) }
						value={ meta[ META.catalogName ] ?? '' }
						onChange={ ( value ) => updateMeta( META.catalogName, value ) }
					/>

					<OfferRepeater
						label={ __( 'Service scope (hasOfferCatalog)', 'isudev-schema-extended' ) }
						help={ __(
							'Only add services that also appear in the visible content. The catalog is not generated without a valid item.',
							'isudev-schema-extended'
						) }
						value={ meta[ META.offers ] ?? [] }
						maxItems={ 20 }
						nameLabel={ __( 'Catalog service name', 'isudev-schema-extended' ) }
						descriptionLabel={ __( 'Description (optional)', 'isudev-schema-extended' ) }
						addLabel={ __( 'Add service', 'isudev-schema-extended' ) }
						removeLabel={ __( 'Remove service', 'isudev-schema-extended' ) }
						onChange={ ( value ) => updateMeta( META.offers, value ) }
					/>

					<TextareaControl
						label={ __( 'Brands', 'isudev-schema-extended' ) }
						help={ __( 'Enter one brand per line, for example Nice or BFT.', 'isudev-schema-extended' ) }
						value={ meta[ META.brands ] ?? '' }
						onChange={ ( value ) => updateMeta( META.brands, value ) }
					/>
				</div>
			) }
		</PanelBody>
	);
};

export default ServicePanel;
