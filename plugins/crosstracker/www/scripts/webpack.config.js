const path = require('path');
const webpack = require('webpack');
const webpack_configurator = require('../../../../tools/utils/scripts/webpack-configurator.js');

const assets_dir_path = path.resolve(__dirname, '../assets');

const webpack_config = {
    entry: {
        'cross-tracker': './cross-tracker/src/app/index.js'
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        alias: {
            'plugin-tracker-TQL': path.resolve(
                __dirname,
                '../../../tracker/www/scripts/report/TQL-CodeMirror'
            )
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(
                webpack_configurator.babel_options_karma
            ),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getMomentLocalePlugin()
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
} else if (
    process.env.NODE_ENV === 'test' ||
    process.env.NODE_ENV === 'watch'
) {
    webpack_config.devtool = 'cheap-eval-source-map';
}

module.exports = webpack_config;
