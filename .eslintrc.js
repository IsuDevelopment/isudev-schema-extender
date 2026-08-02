module.exports = {
	extends: ['plugin:@wordpress/eslint-plugin/recommended'],
	globals: {
		wp: 'off',
	},
	env: {
		browser: true,
		node: true,
	},
	parserOptions: {
		warnOnUnsupportedTypeScriptVersion: false,
	},
	rules: {
		'jsdoc/require-param': 'off',
		'@wordpress/dependency-group': 'error',
		'@wordpress/no-unsafe-wp-apis': 'off',
		'@wordpress/i18n-no-collapsible-whitespace': 'off',
		'react-hooks/exhaustive-deps': 'off',
	},
};
