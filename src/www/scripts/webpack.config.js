/* eslint-disable */
var path                        = require('path');
var webpack                     = require('webpack');
var WebpackAssetsManifest       = require('webpack-assets-manifest');
var BabelPresetEnv              = require('babel-preset-env');
var BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');
var polyfills_for_fetch         = require('../../../tools/utils/ie11-polyfill-names.js').polyfills_for_fetch;

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
            merge : true,
        }),
        new webpack.optimize.CommonsChunkPlugin({
            name: 'dashboard'
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

var webpack_config_for_flaming_parrot_code = {
    entry: {
        'flamingparrot-with-polyfills': polyfills_for_fetch.concat([
            './FlamingParrot/index.js'
        ]),
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    externals: {
        'jquery': 'jQuery',
        'tuleap': 'tuleap'
    },
    resolve: {
        alias: {
            // keymaster-sequence isn't on npm
            'keymaster-sequence': path.resolve(__dirname, './FlamingParrot/keymaster-sequence/keymaster.sequence.min.js'),
            // navbar-history-flamingparrot needs this because TLP is not included in FlamingParrot
            // We use tlp.get() and tlp.put(). This means we need polyfills for fetch() and Promise
            'tlp-fetch': path.resolve(__dirname, '../themes/common/tlp/src/js/fetch-wrapper.js')
        }
    },
    module: {
        rules: [
            babel_rule,
            {
                test: /keymaster\.sequence\.min\.js$/,
                use : 'imports-loader?key=keymaster'
            }
        ]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            assets: manifest_data,
            merge : true,
        })
    ]
};

var webpack_config_for_labels = {
    entry: {
        'labels-box': './labels/labels-box.js'
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js',
        library: 'LabelsBox'
    },
    externals: {
        jquery: 'jQuery'
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
            merge : true,
        })
    ]
};

const webpack_config_for_buring_parrot_code = {
    entry: {
        'burning-parrot'       : './BurningParrot/index.js',
        'project-admin'        : './project/admin/index.js',
        'project-admin-ugroups': './project/admin//project-admin-ugroups.js',
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    externals: {
        tlp: 'tlp'
    },
    module: {
        rules: [
            babel_rule,
            {
                test: /\.mustache$/,
                use: { loader: 'raw-loader' }
            },
            {
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
            merge : true,
            writeToDisk: true
        })
    ]
};

if (process.env.NODE_ENV === 'production') {
    const optimized_configs = [
        webpack_config_for_dashboards,
        webpack_config_for_labels,
        webpack_config_for_flaming_parrot_code,
        webpack_config_for_buring_parrot_code
    ];
    optimized_configs.forEach(function (config) {
        return config.plugins = config.plugins.concat([
            new webpack.optimize.ModuleConcatenationPlugin()
        ]);
    });
}

module.exports = [
    webpack_config_for_dashboards,
    webpack_config_for_labels,
    webpack_config_for_flaming_parrot_code,
    webpack_config_for_buring_parrot_code,
];
