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
		label: __( 'Miejscowość (City)', 'isudev-schema-extended' ),
		value: 'City',
	},
	{
		label: __( 'Region administracyjny (AdministrativeArea)', 'isudev-schema-extended' ),
		value: 'AdministrativeArea',
	},
	{
		label: __( 'Kraj (Country)', 'isudev-schema-extended' ),
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
		<PanelBody title={ __( 'Usługa (Service)', 'isudev-schema-extended' ) } initialOpen>
			<ToggleControl
				label={ __( 'Dodaj Service do schematu', 'isudev-schema-extended' ) }
				help={ __( 'Usługa zostanie dołączona do istniejącego grafu Yoast SEO.', 'isudev-schema-extended' ) }
				checked={ enabled }
				onChange={ ( value ) => updateMeta( META.enabled, value ) }
			/>

			{ enabled && (
				<div className="isudev-schema-extended__fields">
					<TextControl
						label={ __( 'Nazwa usługi', 'isudev-schema-extended' ) }
						help={ __( 'Pozostaw puste, aby użyć tytułu strony.', 'isudev-schema-extended' ) }
						value={ meta[ META.name ] ?? '' }
						onChange={ ( value ) => updateMeta( META.name, value ) }
					/>

					<TextControl
						label={ __( 'Typ usługi', 'isudev-schema-extended' ) }
						help={ __( 'Przykład: Montaż i serwis automatyki do bram.', 'isudev-schema-extended' ) }
						value={ meta[ META.serviceType ] ?? '' }
						onChange={ ( value ) => updateMeta( META.serviceType, value ) }
					/>

					<TextareaControl
						label={ __( 'Opis usługi', 'isudev-schema-extended' ) }
						help={ __( 'Pozostaw puste, aby użyć opisu SEO Yoasta.', 'isudev-schema-extended' ) }
						value={ meta[ META.description ] ?? '' }
						onChange={ ( value ) => updateMeta( META.description, value ) }
					/>

					<TypedNameRepeater
						label={ __( 'Obszary działania', 'isudev-schema-extended' ) }
						help={ __(
							'Dodaj każdy obszar osobno i wybierz jego prawidłowy typ Schema.org.',
							'isudev-schema-extended'
						) }
						value={ areas }
						typeOptions={ AREA_TYPES }
						typeLabel={ __( 'Typ obszaru', 'isudev-schema-extended' ) }
						nameLabel={ __( 'Nazwa obszaru', 'isudev-schema-extended' ) }
						addLabel={ __( 'Dodaj obszar', 'isudev-schema-extended' ) }
						removeLabel={ __( 'Usuń obszar', 'isudev-schema-extended' ) }
						onChange={ ( value ) =>
							updateMetaValues( {
								[ META.areas ]: value,
								[ META.legacyAreaServed ]: '',
							} )
						}
					/>

					<TextControl
						label={ __( 'Nazwa katalogu usług', 'isudev-schema-extended' ) }
						help={ __(
							'Przykład: Zakres usług monitoringu we Wrocławiu. Pozostaw puste, aby utworzyć nazwę z nazwy usługi.',
							'isudev-schema-extended'
						) }
						value={ meta[ META.catalogName ] ?? '' }
						onChange={ ( value ) => updateMeta( META.catalogName, value ) }
					/>

					<OfferRepeater
						label={ __( 'Zakres usług (hasOfferCatalog)', 'isudev-schema-extended' ) }
						help={ __(
							'Dodawaj wyłącznie usługi opisane również w widocznej treści strony. Katalog nie jest generowany bez poprawnej pozycji.',
							'isudev-schema-extended'
						) }
						value={ meta[ META.offers ] ?? [] }
						maxItems={ 20 }
						nameLabel={ __( 'Nazwa usługi w katalogu', 'isudev-schema-extended' ) }
						descriptionLabel={ __( 'Opis (opcjonalny)', 'isudev-schema-extended' ) }
						addLabel={ __( 'Dodaj usługę', 'isudev-schema-extended' ) }
						removeLabel={ __( 'Usuń usługę', 'isudev-schema-extended' ) }
						onChange={ ( value ) => updateMeta( META.offers, value ) }
					/>

					<TextareaControl
						label={ __( 'Marki', 'isudev-schema-extended' ) }
						help={ __( 'Jedna marka w wierszu, np. Nice lub BFT.', 'isudev-schema-extended' ) }
						value={ meta[ META.brands ] ?? '' }
						onChange={ ( value ) => updateMeta( META.brands, value ) }
					/>
				</div>
			) }
		</PanelBody>
	);
};

export default ServicePanel;
