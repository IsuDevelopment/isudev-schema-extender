const { Button, Card, CardBody, TextControl, TextareaControl } = window.wp.components;

const normalizeItems = ( value ) =>
	Array.isArray( value )
		? value.filter( ( item ) => item && typeof item === 'object' && typeof item.name === 'string' )
		: [];

/**
 * Reusable editor control for catalog items serialized as Offer > Service.
 *
 * Prices and availability deliberately stay out of this component: the
 * catalog describes service scope, not a transactional product offer.
 */
const OfferRepeater = ( {
	addLabel,
	descriptionLabel,
	help,
	label,
	maxItems = 20,
	nameLabel,
	onChange,
	removeLabel,
	value,
} ) => {
	const items = normalizeItems( value );

	const updateItem = ( index, property, propertyValue ) => {
		onChange(
			items.map( ( item, itemIndex ) =>
				itemIndex === index
					? {
							...item,
							[ property ]: propertyValue,
					  }
					: item
			)
		);
	};

	const removeItem = ( index ) => {
		onChange( items.filter( ( _, itemIndex ) => itemIndex !== index ) );
	};

	const addItem = () => {
		if ( items.length >= maxItems ) {
			return;
		}

		onChange( [
			...items,
			{
				name: '',
				description: '',
			},
		] );
	};

	return (
		<div className="isudev-offer-repeater" role="group" aria-label={ label }>
			<div className="isudev-offer-repeater__heading">
				<strong>{ label }</strong>
				{ help && <p className="components-base-control__help">{ help }</p> }
			</div>

			{ items.map( ( item, index ) => (
				<Card className="isudev-offer-repeater__item" key={ `offer-${ index }` } size="small">
					<CardBody className="isudev-offer-repeater__item-body">
						<TextControl
							label={ nameLabel }
							value={ item.name }
							onChange={ ( nextName ) => updateItem( index, 'name', nextName ) }
						/>
						<TextareaControl
							label={ descriptionLabel }
							value={ item.description ?? '' }
							onChange={ ( nextDescription ) => updateItem( index, 'description', nextDescription ) }
						/>
						<Button isDestructive variant="secondary" onClick={ () => removeItem( index ) }>
							{ removeLabel }
						</Button>
					</CardBody>
				</Card>
			) ) }

			<Button
				className="isudev-offer-repeater__add"
				variant="secondary"
				onClick={ addItem }
				disabled={ items.length >= maxItems }
			>
				{ addLabel }
			</Button>
		</div>
	);
};

export default OfferRepeater;
