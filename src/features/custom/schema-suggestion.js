/**
 * Internal dependencies
 */
import runAbility from './run-ability';

const { Button, ExternalLink, Modal, Notice } = window.wp.components;
const { select, useDispatch, useSelect } = window.wp.data;
const { useState } = window.wp.element;
const { __, sprintf } = window.wp.i18n;

const config = window.isudevSchemaExtended?.suggest ?? { enabled: false };

const SuggestionModal = ( { suggestion, currentSource, onApply, onClose } ) => {
	const proposed = suggestion.proposed_source ?? '';
	const unchanged = proposed.trim() === currentSource.trim();
	const errors = suggestion.validation?.errors ?? [];

	return (
		<Modal
			title={ __( 'Custom Schema proposal', 'isudev-schema-extended' ) }
			onRequestClose={ onClose }
			size="large"
			className="isudev-schema-suggestion"
		>
			{ suggestion.analysis && <p className="isudev-schema-suggestion__analysis">{ suggestion.analysis }</p> }

			{ suggestion.gaps?.length > 0 && (
				<>
					<h3>{ __( 'Missing entities', 'isudev-schema-extended' ) }</h3>
					<ul className="isudev-schema-suggestion__gaps">
						{ suggestion.gaps.map( ( gap, index ) => (
							<li key={ index }>
								<strong>{ gap.title }</strong>
								{ gap.reason && ` — ${ gap.reason }` }
							</li>
						) ) }
					</ul>
				</>
			) }

			{ suggestion.removed?.length > 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ sprintf(
						/* translators: %s: comma-separated Schema.org types and properties. */
						__(
							'Removed from the proposal because they need verifiable on-page content: %s.',
							'isudev-schema-extended'
						),
						suggestion.removed.join( ', ' )
					) }
				</Notice>
			) }

			{ proposed.trim() === '' || unchanged ? (
				<Notice status="info" isDismissible={ false }>
					{ __( 'The AI found nothing to add to the current graph.', 'isudev-schema-extended' ) }
				</Notice>
			) : (
				<>
					{ errors.length > 0 && (
						<Notice status="error" isDismissible={ false }>
							{ __(
								'The proposal does not pass validation yet. You can apply it and fix it in the editor:',
								'isudev-schema-extended'
							) }
							<ul>
								{ errors.map( ( error, index ) => (
									<li key={ index }>{ error.message }</li>
								) ) }
							</ul>
						</Notice>
					) }
					<div className="isudev-schema-suggestion__compare">
						<div>
							<h3>{ __( 'Current', 'isudev-schema-extended' ) }</h3>
							<pre>{ currentSource || __( '(empty)', 'isudev-schema-extended' ) }</pre>
						</div>
						<div>
							<h3>{ __( 'Proposed', 'isudev-schema-extended' ) }</h3>
							<pre>{ proposed }</pre>
						</div>
					</div>
				</>
			) }

			<div className="isudev-schema-suggestion__actions">
				<Button variant="tertiary" onClick={ onClose }>
					{ __( 'Cancel', 'isudev-schema-extended' ) }
				</Button>
				<Button
					variant="primary"
					disabled={ proposed.trim() === '' || unchanged }
					onClick={ () => onApply( proposed ) }
				>
					{ __( 'Replace JSON in the editor', 'isudev-schema-extended' ) }
				</Button>
			</div>
		</Modal>
	);
};

const SchemaSuggestion = ( { source, onApply } ) => {
	const [ isBusy, setIsBusy ] = useState( false );
	const [ suggestion, setSuggestion ] = useState( null );
	const postId = useSelect( ( storeSelect ) => storeSelect( 'core/editor' ).getCurrentPostId(), [] );
	const { createErrorNotice, createSuccessNotice } = useDispatch( 'core/notices' );

	if ( ! config.enabled ) {
		return null;
	}

	const explore = async () => {
		setIsBusy( true );
		try {
			// Serialized on click only: selecting it in render would re-serialize on every keystroke.
			const input = { post_id: postId, content: select( 'core/editor' ).getEditedPostContent() };
			if ( source !== '' ) {
				input.source = source;
			}
			setSuggestion( await runAbility( config.ability, input ) );
		} catch ( error ) {
			createErrorNotice(
				error?.message || __( 'The AI proposal could not be generated.', 'isudev-schema-extended' ),
				{ type: 'snackbar' }
			);
		} finally {
			setIsBusy( false );
		}
	};

	const apply = ( proposed ) => {
		onApply( proposed );
		setSuggestion( null );
		createSuccessNotice(
			__( 'Proposal applied. Review it and save the post to keep it.', 'isudev-schema-extended' ),
			{ type: 'snackbar' }
		);
	};

	return (
		<div className="isudev-schema-suggestion__trigger">
			<Button
				variant="secondary"
				isBusy={ isBusy }
				disabled={ isBusy || ! config.hasProvider }
				accessibleWhenDisabled
				onClick={ explore }
			>
				{ isBusy
					? __( 'Exploring schema…', 'isudev-schema-extended' )
					: __( 'Explore & extend schema', 'isudev-schema-extended' ) }
			</Button>

			{ ! config.hasProvider && (
				<p className="isudev-custom-schema__help">
					{ __(
						'Connect an AI provider with text generation to use suggestions.',
						'isudev-schema-extended'
					) }{ ' ' }
					<ExternalLink href={ config.connectorsUrl }>
						{ __( 'Connectors', 'isudev-schema-extended' ) }
					</ExternalLink>
				</p>
			) }

			{ suggestion && (
				<SuggestionModal
					suggestion={ suggestion }
					currentSource={ source }
					onApply={ apply }
					onClose={ () => setSuggestion( null ) }
				/>
			) }
		</div>
	);
};

export default SchemaSuggestion;
