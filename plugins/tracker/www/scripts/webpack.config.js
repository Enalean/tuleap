/* eslint-disable */
const path                        = require('path');
const webpack                     = require('webpack');
const WebpackAssetsManifest       = require('webpack-assets-manifest');
const BabelPresetEnv              = require('babel-preset-env');
const BabelPluginIstanbul         = require('babel-plugin-istanbul').default;
const BabelPluginRewireExports    = require('babel-plugin-rewire-exports').default;
const BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

const assets_dir_path = path.resolve(__dirname, '../assets');

const babel_preset_env_ie_config = [BabelPresetEnv, {
    targets: {
        ie: 11
    },
    modules: false
}];

const babel_preset_env_chrome_config = [BabelPresetEnv, {
    targets: {
        browsers: ['last 2 Chrome versions']
    },
    modules: false,
    useBuiltIns: true,
    shippedProposals: true
}];

const babel_options   = {
    env: {
        watch: {
            presets: [babel_preset_env_ie_config],
            plugins: [BabelPluginObjectRestSpread]
        },
        production: {
            presets: [babel_preset_env_ie_config],
            plugins: [BabelPluginObjectRestSpread]
        },
        test: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                BabelPluginObjectRestSpread,
                BabelPluginRewireExports
            ]
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                BabelPluginObjectRestSpread,
                BabelPluginRewireExports,
                [BabelPluginIstanbul, {
                    exclude: ['**/*.spec.js']
                }]
            ]
        }
    }
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

const webpack_config_for_trackers = {
    entry: {
        'tracker-report-expert-mode': './report/index.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    externals: {
        codendi: 'codendi',
    },
    resolve: {
        alias: {
            // TLP is not included in FlamingParrot
            'tlp-fetch': path.resolve(__dirname, '../../../../src/www/themes/common/tlp/src/js/fetch-wrapper.js')
        }
    },
    module: {
        rules: [babel_rule]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true
        })
    ]
};

const webpack_config_for_artifact_modal = {
    entry: './angular-artifact-modal/index.js',
    output  : {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        modules: [
            'node_modules',
            // This ensures that dependencies resolve their imported modules in angular-artifact-modal's node_modules
            path.resolve(__dirname, 'node_modules')
        ],
        alias: {
            'angular-tlp': path.resolve(__dirname, '../../../../src/www/themes/common/tlp/angular-tlp/index.js'),
        }
    },
    module: {
        rules: [
            babel_rule,
            {
                test: /\.html$/,
                exclude: /node_modules/,
                use: [
                    { loader: 'ng-cache-loader' }
                ]
            }, {
                test: /\.po$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'angular-gettext-loader',
                        query: 'browserify=true'
                    }
                ]
            }
        ]
    },
    plugins: [
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
}

const webpack_config_for_burndown_chart = {
    entry: {
        'burndown-chart': './burndown-chart/src/burndown-chart.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
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
            babel_rule,
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
            merge: true,
            writeToDisk: true
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

if (process.env.NODE_ENV === 'watch' || process.env.NODE_ENV === 'test') {
    webpack_config_for_artifact_modal.devtool = 'cheap-module-eval-source-map';
    webpack_config_for_burndown_chart.devtool = 'cheap-module-eval-source-map';
}

if (process.env.NODE_ENV === 'production') {
    webpack_config_for_trackers.plugins = webpack_config_for_trackers.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);

    webpack_config_for_burndown_chart.plugins = webpack_config_for_burndown_chart.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);

    module.exports = [
        webpack_config_for_trackers,
        webpack_config_for_burndown_chart
    ];
} else {
    module.exports = [
        webpack_config_for_trackers,
        webpack_config_for_artifact_modal,
        webpack_config_for_burndown_chart
    ];
}
