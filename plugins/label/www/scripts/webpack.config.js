const path                        = require('path');
const webpack                     = require('webpack');
const WebpackAssetsManifest       = require('webpack-assets-manifest');
const BabelPresetEnv              = require('babel-preset-env');
const VueLoaderOptionsPlugin      = require('vue-loader-options-plugin');
const BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');
const BabelPluginRewireExports    = require('babel-plugin-rewire-exports').default;
const BabelPluginIstanbul         = require('babel-plugin-istanbul').default;

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
            plugins: [
                BabelPluginObjectRestSpread,
            ]
        },
        production: {
            presets: [babel_preset_env_ie_config],
            plugins: [BabelPluginObjectRestSpread]
        },
        test: {
            presets: [babel_preset_env_chrome_config],
            plugins: [BabelPluginObjectRestSpread, BabelPluginRewireExports]
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

const webpack_config = {
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
            'tlp-mocks': path.resolve('../../../../src/www/themes/common/tlp/mocks/index.js'),
        }
    },
    module: {
        rules: [
            babel_rule,
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
        }),
        new VueLoaderOptionsPlugin({
            babel: babel_options
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
} else if (process.env.NODE_ENV === 'watch') {
    webpack_config.devtool = 'eval';
}

module.exports = webpack_config;
