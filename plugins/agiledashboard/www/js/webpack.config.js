/* eslint-disable */
const path                        = require('path');
const webpack                     = require('webpack');
const WebpackAssetsManifest       = require('webpack-assets-manifest');
const BabelPresetEnv              = require('babel-preset-env');
const BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');
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

const webpack_config_for_charts = {
    entry : {
        'burnup-chart': './burnup-chart/src/burnup-chart.js'
    },
    output: {
        path    : assets_dir_path,
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
        rules: [babel_rule, po_rule]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            assets: manifest_data,
            merge: true
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

const path_to_badge = path.resolve(__dirname, '../../../../src/www/scripts/project/admin/permissions-per-group/');

const webpack_config_for_overview_and_vue = {
    entry: {
        'overview'            : './scrum-header.js',
        'permission-per-group': './permissions-per-group/src/index.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        alias: {
            'permission-badge': path_to_badge
        }
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
            output: 'manifest.json',
            assets: manifest_data,
            merge: true,
            writeToDisk: true
        }),
        new VueLoaderOptionsPlugin({
            babel: babel_options
        })
    ]
}

if (process.env.NODE_ENV === 'production') {
    const optimized_configs = [
        webpack_config_for_charts,
        webpack_config_for_overview_and_vue
    ];
    optimized_configs.forEach(config => {
        config.plugins = config.plugins.concat([
            new webpack.optimize.ModuleConcatenationPlugin()
        ])
    })
    webpack_config_for_overview_and_vue.plugins = webpack_config_for_overview_and_vue.plugins.concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        })
    ]);
} else if (process.env.NODE_ENV === 'watch') {
    webpack_config_for_overview_and_vue.devtool = 'eval';
}

module.exports = [
    webpack_config_for_charts,
    webpack_config_for_overview_and_vue
];
