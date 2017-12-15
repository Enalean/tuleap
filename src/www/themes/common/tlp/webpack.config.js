/* eslint-disable */
var path                        = require('path');
var webpack                     = require('webpack');
var WebpackAssetsManifest       = require('webpack-assets-manifest');
var BabelPresetEnv              = require('babel-preset-env');
var BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');
var polyfills_for_fetch         = require('../../../../../tools/utils/ie11-polyfill-names.js').polyfills_for_fetch;

var babel_options = {
    presets: [
        ["babel-preset-env", {
            targets: {
                ie: 11
            },
            modules: false
        }],
    ],
    plugins: [
        BabelPluginObjectRestSpread
    ]
};

var webpack_config = {
    entry : {
        'en_US.min': polyfills_for_fetch.concat([
            'dom4',
            './src/index.en_US.js'
        ]),
        'fr_FR.min': polyfills_for_fetch.concat([
            'dom4',
            './src/index.fr_FR.js'
        ])
    },
    output: {
        path    : path.resolve(__dirname, 'dist/'),
        filename: 'tlp-[chunkhash].[name].js',
        library : 'tlp'
    },
    resolve: {
        modules: ['node_modules'],
        alias: {
            'select2': 'select2/dist/js/select2.full.js'
        }
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'babel-loader',
                        options: babel_options
                    }
                ]
            }
        ]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true,
            customize: function(key, value) {
                return {
                    key  : `tlp.${key}`,
                    value: value
                }
            }
        })
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = webpack_config;
