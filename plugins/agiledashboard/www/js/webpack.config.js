const path = require('path');
const webpack_configurator = require('../../../../tools/utils/scripts/webpack-configurator.js');

const assets_dir_path = path.resolve(__dirname, '../assets');
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_charts = {
    entry: {
        'burnup-chart': './burnup-chart/src/burnup-chart.js'
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    resolve: {
        modules: [
            path.resolve(__dirname, 'node_modules'),
        ],
        alias: {
            'charts-builders': path.resolve(__dirname, '../../../../src/www/scripts/charts-builders/')
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getMomentLocalePlugin()
    ]
};

const path_to_badge = path.resolve(__dirname, '../../../../src/www/scripts/project/admin/permissions-per-group/');

const webpack_config_for_overview_and_vue = {
    entry: {
        overview: './scrum-header.js',
        'permission-per-group': './permissions-per-group/src/index.js'
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        alias: {
            'permission-badge': path_to_badge
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()]
};

module.exports = [
    webpack_config_for_charts,
    webpack_config_for_overview_and_vue
];
