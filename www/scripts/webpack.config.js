const path                 = require('path');
const webpack_configurator = require('../../../../tools/utils/scripts/webpack-configurator.js');

const assets_dir_path = path.resolve(__dirname, '../assets');

module.exports = [
    {
        entry: {
            'step-definition-field': './step-definition-field/index.js'
        },
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(assets_dir_path),
        externals: {
            tuleap: 'tuleap'
        },
        module: {
            rules: [
                webpack_configurator.configureBabelRule(
                    webpack_configurator.babel_options_ie11
                ),
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader
            ]
        },
        plugins: [
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getVueLoaderPlugin()
        ],
        resolveLoader: {
            alias: webpack_configurator.extendAliases(
                {},
                webpack_configurator.easygettext_loader_alias
            )
        }
    }
];
