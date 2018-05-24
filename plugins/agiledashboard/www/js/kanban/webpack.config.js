const path = require('path');
const webpack_configurator = require('../../../../../tools/utils/scripts/webpack-configurator.js');

const assets_dir_path = path.resolve(__dirname, './dist');
const path_to_tlp = path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/');
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_kanban = {
    entry : {
        kanban: './src/app/app.js',
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: 'tlp',
        angular: 'angular',
        jquery: 'jQuery'
    },
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in kanban's node_modules
            path.resolve(__dirname, 'node_modules'),
            'node_modules'
        ],
        alias: webpack_configurator.extendAliases(
            webpack_configurator.angular_artifact_modal_aliases,
            {
                'cumulative-flow-diagram': path.resolve(
                    __dirname,
                    '../cumulative-flow-diagram/index.js'
                ),
                'card-fields': path.resolve(__dirname, '../card-fields'),
                'angular-tlp': path.join(path_to_tlp, 'angular-tlp')
            }
        )
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_karma),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader
        ]
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getMomentLocalePlugin()
    ]
};

const webpack_config_for_angular = {
    entry : {
        angular: 'angular'
    },
    output: webpack_configurator.configureOutput(assets_dir_path),
    plugins: [
        manifest_plugin
    ]
};

module.exports = [
    webpack_config_for_kanban,
    webpack_config_for_angular
];
