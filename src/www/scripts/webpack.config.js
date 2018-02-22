/* eslint-disable */
const path                        = require('path');
const webpack                     = require('webpack');
const WebpackAssetsManifest       = require('webpack-assets-manifest');
const BabelPresetEnv              = require('babel-preset-env');
const BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');
const polyfills_for_fetch         = require('../../../tools/utils/ie11-polyfill-names.js').polyfills_for_fetch;
const VueLoaderOptionsPlugin      = require('vue-loader-options-plugin');

const manifest_data   = Object.create(null);
const assets_dir_path = path.resolve(__dirname, '../assets');

const babel_preset_env_ie_config = [BabelPresetEnv, {
    targets: {
        ie: 11
    },
    modules: false
}];

const babel_options = {
    presets: [babel_preset_env_ie_config],
    plugins: [BabelPluginObjectRestSpread]
};

const babel_rule = {
    test: /\.js$/,
    exclude: /node_modules/,
    use: [
        {
            loader: 'babel-loader',
            options: babel_options
        }
    ]
};

const po_rule = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [
        { loader: 'json-loader' },
        { loader: 'po-gettext-loader' }
    ]
};

const webpack_config_for_dashboards = {
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

const webpack_config_for_flaming_parrot_code = {
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

const webpack_config_for_labels = {
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

const webpack_config_for_burning_parrot_code = {
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
            po_rule,
            {
                test: /\.mustache$/,
                use: { loader: 'raw-loader' }
            }
        ]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            assets: manifest_data,
            merge : true
        })
    ]
};

const webpack_config_for_vue_components = {
    entry: {
        'news-permissions': './news/permissions-per-group/index.js'
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
            po_rule,
            {
                test: /\.vue$/,
                use: [
                    {
                        loader: 'vue-loader',
                        options: {
                            loaders: {
                                js: 'babel-loader'
                            },
                            esModule: true
                        }
                    }
                ]
            }
        ]
    },
    plugins: [
        new WebpackAssetsManifest({
            output     : 'manifest.json',
            assets     : manifest_data,
            merge      : true,
            writeToDisk: true
        }),
        new VueLoaderOptionsPlugin({
            babel: babel_options
        })
    ]
};

if (process.env.NODE_ENV === 'production') {
    const optimized_configs = [
        webpack_config_for_dashboards,
        webpack_config_for_labels,
        webpack_config_for_flaming_parrot_code,
        webpack_config_for_burning_parrot_code,
        webpack_config_for_vue_components
    ];
    optimized_configs.forEach(config => {
        config.plugins = config.plugins.concat([
            new webpack.optimize.ModuleConcatenationPlugin()
        ]);
    });
    webpack_config_for_vue_components.plugins = webpack_config_for_vue_components.plugins.concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        })
    ]);
} else if (process.env.NODE_ENV === 'watch') {
    webpack_config_for_vue_components.devtool = 'eval';
}

module.exports = [
    webpack_config_for_dashboards,
    webpack_config_for_labels,
    webpack_config_for_flaming_parrot_code,
    webpack_config_for_burning_parrot_code,
    webpack_config_for_vue_components
];
