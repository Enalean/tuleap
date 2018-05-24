const path = require('path');
const webpack_configurator = require('../../../../../tools/utils/scripts/webpack-configurator.js');

const assets_dir_path = path.resolve(__dirname, './dist');

const webpack_config = {
    entry : {
        'planning-v2': './src/app/app.js'
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: 'tlp',
        jquery: 'jQuery'
    },
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in planning's node_modules
            path.resolve(__dirname, 'node_modules'),
            'node_modules'
        ],
        alias: webpack_configurator.extendAliases({
            'card-fields'           : path.resolve(__dirname, '../card-fields')
        }, webpack_configurator.angular_artifact_modal_aliases)
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_karma),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader
        ]
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getMomentLocalePlugin()
    ]
};

module.exports = webpack_config;
