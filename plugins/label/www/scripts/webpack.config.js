/* eslint-disable */
var path                        = require('path');
var webpack                     = require('webpack');
var WebpackAssetsManifest       = require('webpack-assets-manifest');
var BabelPresetEnv              = require('babel-preset-env');
var BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

var manifest_data   = Object.create(null);
var assets_dir_path = path.resolve(__dirname, '../assets');

var webpack_config_for_configure = {
    entry: {
        'configure-widget': './configure-widget/index.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    externals: {
        tlp: 'tlp'
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
            assets: manifest_data,
        })
    ]
};

var webpack_config_for_widget = {
    entry : {
        'widget-project-labeled-items': './project-labeled-items/src/index.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        }
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: [
                    { loader: 'babel-loader' }
                ]
            }, {
                test: /\.vue$/,
                use: [
                    { loader: 'vue-loader' }
                ]
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
    webpack_config_for_widget.plugins = webpack_config_for_widget.plugins.concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = [
    webpack_config_for_configure,
    webpack_config_for_widget
];
