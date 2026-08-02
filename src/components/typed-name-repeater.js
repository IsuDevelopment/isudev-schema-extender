const { Button, Card, CardBody, SelectControl, TextControl } = window.wp.components;

const normalizeItems = ( value ) =>
	Array.isArray( value )
		? value.filter(
				( item ) =>
					item && typeof item === 'object' && typeof item.type === 'string' && typeof item.name === 'string'
		  )
		: [];

/**
 * Reusable editor control for schema values represented by a type and a name.
 *
 * The component deliberately stores domain data (`type`, `name`) rather than
 * JSON-LD keys. Schema serialization remains a server-side responsibility.
 */
const TypedNameRepeater = ( {
	addLabel,
	help,
	label,
	nameLabel,
	onChange,
	removeLabel,
	typeLabel,
	typeOptions,
	value,
} ) => {
	const items = normalizeItems( value );
	const defaultType = typeOptions[ 0 ]?.value ?? '';

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
		onChange( [
			...items,
			{
				type: defaultType,
				name: '',
			},
		] );
	};

	return (
		<div className="isudev-typed-name-repeater" role="group" aria-label={ label }>
			<div className="isudev-typed-name-repeater__heading">
				<strong>{ label }</strong>
				{ help && <p className="components-base-control__help">{ help }</p> }
			</div>

			{ items.map( ( item, index ) => (
				<Card className="isudev-typed-name-repeater__item" key={ `${ item.type }-${ index }` } size="small">
					<CardBody className="isudev-typed-name-repeater__item-body">
						<SelectControl
							label={ typeLabel }
							options={ typeOptions }
							value={ item.type }
							onChange={ ( nextType ) => updateItem( index, 'type', nextType ) }
						/>
						<TextControl
							label={ nameLabel }
							value={ item.name }
							onChange={ ( nextName ) => updateItem( index, 'name', nextName ) }
						/>
						<Button isDestructive variant="secondary" onClick={ () => removeItem( index ) }>
							{ removeLabel }
						</Button>
					</CardBody>
				</Card>
			) ) }

			<Button
				className="isudev-typed-name-repeater__add"
				variant="secondary"
				onClick={ addItem }
				disabled={ ! defaultType }
			>
				{ addLabel }
			</Button>
		</div>
	);
};

export default TypedNameRepeater;
