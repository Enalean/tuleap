/* eslint-disable */
const path                        = require('path');
const webpack                     = require('webpack');
const WebpackAssetsManifest       = require('webpack-assets-manifest');
const BabelPresetEnv              = require('babel-preset-env');
const BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

const build_dir_path = path.resolve(__dirname, '../assets');

const babel_options = {
    presets: [
        [BabelPresetEnv, {
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

module.exports = {
    entry : {
       'burnup-chart': './burnup-chart/src/burnup-chart.js'
    },
    output: {
        path    : build_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: babel_options
                }
            }, {
                test: /\.po$/,
                exclude: /node_modules/,
                use: [
                    { loader: 'json-loader' },
                    { loader: 'po-gettext-loader' }
                ]
            }
        ]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};
