/* eslint-disable */
const path                        = require('path');
const webpack                     = require('webpack');
const WebpackAssetsManifest       = require('webpack-assets-manifest');
const BabelPresetEnv              = require('babel-preset-env');
const BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

const build_dir_path = path.resolve(__dirname, '../assets');

const manifest_data   = Object.create(null);

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

const webpack_config_for_overview = {
    entry : {
        'overview': './overview.js'
    },
    output: {
        path    : build_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    externals: {
        tlp: 'tlp'
    },
    module: {
        rules: [{
            test   : /\.js$/,
            exclude: /node_modules/,
            use    : [
                {
                    loader : 'babel-loader',
                    options: babel_options
                }
            ]
        }]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            assets: manifest_data,
            merge : true,
        })
    ]
};

const webpack_config_for_charts = {
    entry : {
        'burnup-chart': './burnup-chart/src/burnup-chart.js'
    },
    output: {
        path    : build_dir_path,
        filename: '[name]-[chunkhash].js'
    },
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
            assets: manifest_data,
            merge: true,
            writeToDisk: true
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config_for_charts.plugins = webpack_config_for_charts.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = [
    webpack_config_for_overview,
    webpack_config_for_charts
];
