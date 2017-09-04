/* eslint-disable */
var path                        = require('path');
var webpack                     = require('webpack');
var WebpackAssetsManifest       = require('webpack-assets-manifest');
var BabelPresetEnv              = require('babel-preset-env');
var BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

var manifest_data   = Object.create(null);
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

var webpack_config_for_dashboards = {
    entry: {
        dashboard                  : './dashboards/dashboard.js',
        'widget-project-heartbeat' : './dashboards/widgets/project-heartbeat/index.js',
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    externals: {
        jquery: 'jQuery',
        tlp   : 'tlp'
    },
    module: {
        rules: [babel_rule]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            assets: manifest_data,
        }),
        new webpack.optimize.CommonsChunkPlugin({
            name: 'dashboard'
        }),
        new webpack.optimize.ModuleConcatenationPlugin(),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

var webpack_config_for_navbar_history = {
    entry: {
        'navbar-history'              : './navbar-history/index-burningparrot.js',
        'navbar-history-flamingparrot': [
            'regenerator-runtime/runtime', // for async/await
            'whatwg-fetch',
            './navbar-history/index-flamingparrot.js'
        ]
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    resolve: {
        alias: {
            // navbar-history-flamingparrot needs this because TLP is not included in FlamingParrot
            // We use tlp.get() and tlp.put(). This means we need polyfills for fetch() and Promise
            'tlp-fetch': path.resolve(__dirname, '../themes/common/tlp/src/js/fetch-wrapper.js')
        }
    },
    externals: {
        tlp   : 'tlp'
    },
    module: {
        rules: [babel_rule]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            assets: manifest_data,
        })
    ]
};

var webpack_config_for_labels = {
    entry: {
        'labels-box': [
            'regenerator-runtime/runtime', // for async/await
            'whatwg-fetch',
            './labels/labels-box.js'
        ]
    },
    externals: {
        jquery: 'jQuery'
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js',
        library: 'LabelsBox'
    },
    resolve: {
        alias: {
            // labels-box needs this because TLP is not included in FlamingParrot
            // We use tlp.get() and tlp.put(). This means we need polyfills for fetch() and Promise
            'tlp-fetch': path.resolve(__dirname, '../themes/common/tlp/src/js/fetch-wrapper.js')
        }
    },
    module: {
        rules: [babel_rule]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            assets: manifest_data,
            merge: true,
            writeToDisk: true
        })
    ]
};

module.exports = [
    webpack_config_for_navbar_history,
    webpack_config_for_dashboards,
    webpack_config_for_labels,
];
