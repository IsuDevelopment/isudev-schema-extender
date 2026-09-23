const apiFetch = window.wp.apiFetch;

/**
 * Run a non-readonly ability through the core Abilities REST endpoint.
 *
 * Only POST abilities go through here: core maps readonly abilities to GET, whose query
 * string cannot carry editor content.
 *
 * @param {string} name  Ability name.
 * @param {Object} input Ability input.
 * @return {Promise<Object>} Ability output.
 */
const runAbility = ( name, input ) =>
	apiFetch( {
		path: `/wp-abilities/v1/abilities/${ name }/run`,
		method: 'POST',
		data: { input },
	} );

export default runAbility;
