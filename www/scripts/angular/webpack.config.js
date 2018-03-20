const path                     = require('path');
const webpack                  = require('webpack');
const WebpackAssetsManifest    = require('webpack-assets-manifest');
const BabelPresetEnv           = require('babel-preset-env');
const BabelPluginIstanbul      = require('babel-plugin-istanbul').default;
const BabelPluginRewireExports = require('babel-plugin-rewire-exports').default;

const assets_dir_path = path.resolve(__dirname, './bin/assets');

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
            presets: [babel_preset_env_ie_config]
        },
        production: {
            presets: [babel_preset_env_ie_config]
        },
        test: {
            presets: [babel_preset_env_chrome_config],
            plugins: [BabelPluginRewireExports]
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                BabelPluginRewireExports,
                [BabelPluginIstanbul, {
                    exclude: ['**/*.spec.js']
                }]
            ]
        }
    }
};

const webpack_config = {
    entry : {
        testmanagement: './src/app/app.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in testmanagement's node_modules
            path.resolve(__dirname, 'node_modules'),
            'node_modules'
        ],
        alias: {
            'angular-artifact-modal': path.resolve(__dirname, '../../../../tracker/www/scripts/angular-artifact-modal'),
            'angular-tlp'           : path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/angular-tlp'),
        }
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
                        options: babel_options
                    }
                ]
            }, {
                test: /\.html$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'ng-cache-loader',
                        query: '-url'
                    }
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
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, /fr/)
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = webpack_config;
