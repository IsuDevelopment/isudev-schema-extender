/**
 * Internal dependencies
 */
import ServicePanel from './features/service/service-panel';
import './editor.css';

const { PluginSidebar } = window.wp.editor;
const { __ } = window.wp.i18n;
const { registerPlugin } = window.wp.plugins;

const schemaIcon = 'admin-tools';

const SchemaExtendedSidebar = () => (
	<PluginSidebar
		name="isudev-schema-extended"
		title={ __( 'Schema Extended', 'isudev-schema-extended' ) }
		icon={ schemaIcon }
	>
		<ServicePanel />
	</PluginSidebar>
);

registerPlugin( 'isudev-schema-extended', {
	render: SchemaExtendedSidebar,
	icon: schemaIcon,
} );
