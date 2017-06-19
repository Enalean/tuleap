/* eslint-disable */
var path                  = require('path');
var WebpackAssetsManifest = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, '../assets');
module.exports = {
    entry: {
        dashboard                     : './dashboards/dashboard.js',
        'navbar-history'              : './navbar-history/index-burningparrot.js',
        'navbar-history-flamingparrot': [
            'whatwg-fetch',
            './navbar-history/index-flamingparrot.js'
        ]
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    resolve: {
        modules: [ 'node_modules' ],
        alias: {
            // navbar-history-flamingparrot needs this because TLP is not included in FlamingParrot
            // We use tlp.get() and tlp.put(). This means we need polyfills for fetch() and Promise
            'tlp-fetch': path.resolve(__dirname, '../themes/common/tlp/src/js/fetch-wrapper.js')
        }
    },
    externals: {
        jquery: 'jQuery',
        tlp   : 'tlp'
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
                                ['env', {
                                    targets: {
                                        ie: 11
                                    },
                                    modules: false
                                }]
                            ],
                            plugins: [
                                "transform-object-rest-spread"
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
            writeToDisk: true
        })
    ]
};
