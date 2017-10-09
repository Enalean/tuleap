/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, '../assets');

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
        "babel-plugin-transform-object-rest-spread"
    ],
    env: {
        test: {
            plugins: [
                "babel-plugin-rewire-exports"
            ]
        },
        coverage: {
            plugins: [
                "babel-plugin-rewire-exports",
                ["babel-plugin-istanbul", {
                    exclude: [ "**/*.spec.js"]
                }]
            ]
        }
    }
};

var webpack_config = {
    entry : {
        'widget-project-labeled-items': './project-labeled-items/src/index.js',
        'configure-widget'            : './configure-widget/index.js'
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
                    {
                        loader: 'babel-loader',
                        options: babel_options
                    }
                ]
            }, {
                test: /\.vue$/,
                use: [
                    {
                        loader: 'vue-loader',
                        options: {
                            loaders: {
                                js: 'babel-loader?' + JSON.stringify(babel_options)
                            }
                        }
                    }
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
            merge: true,
            writeToDisk: true
        })
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
}

module.exports = webpack_config;
