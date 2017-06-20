/* eslint-disable */
var path                        = require('path');
var WebpackAssetsManifest       = require('webpack-assets-manifest');
var BabelPresetEnv              = require('babel-preset-env');
var BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

module.exports = {
    entry : {
        'en_US.min': [
            'babel-polyfill',
            'dom4',
            'whatwg-fetch',
            './src/index.en_US.js'
        ],
        'fr_FR.min': [
            'babel-polyfill',
            'dom4',
            'whatwg-fetch',
            './src/index.fr_FR.js'
        ]
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
