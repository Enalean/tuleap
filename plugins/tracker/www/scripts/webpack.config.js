/* eslint-disable */
var path                        = require('path');
var webpack                     = require('webpack');
var WebpackAssetsManifest       = require('webpack-assets-manifest');
var BabelPresetEnv              = require('babel-preset-env');
var BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

var assets_dir_path = path.resolve(__dirname, '../assets');

var babel_rule = {
    test: /\.js$/,
    exclude: /node_modules/,
    use: [
        {
            loader: 'babel-loader',
            options: {
                presets: [
                    [BabelPresetEnv, {
                        targets: {
                            ie: 11
                        },
                        modules: false
                    }]
                ],
                plugins: [
                    BabelPluginObjectRestSpread
                ]
            }
        }
    ]
};

var webpack_config = {
    entry: {
        'tracker-report-expert-mode': './report/index.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    externals: {
        codendi: 'codendi',
    },
    resolve: {
        alias: {
            // TLP is not included in FlamingParrot
            'tlp-fetch': path.resolve(__dirname, '../../../../src/www/themes/common/tlp/src/js/fetch-wrapper.js')
        }
    },
    module: {
        rules: [babel_rule]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true
        })
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = webpack_config;
