const path = require('path');
const webpack_configurator = require('../../../../../tools/utils/scripts/webpack-configurator.js');

const assets_dir_path = path.resolve(__dirname, './dist');
const path_to_tlp = path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/');

const webpack_config = {
    entry : {
        'planning-v2': './src/app/app.js'
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in planning's node_modules
            path.resolve(__dirname, 'node_modules'),
            'node_modules'
        ],
        alias: {
            'angular-artifact-modal': path.resolve(__dirname, '../../../../tracker/www/scripts/angular-artifact-modal/index.js'),
            'angular-tlp'           : path.join(path_to_tlp, 'angular-tlp/index.js'),
            'tlp-mocks'             : path.join(path_to_tlp, 'mocks/index.js'),
            'card-fields'           : path.resolve(__dirname, '../card-fields')
        }
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
