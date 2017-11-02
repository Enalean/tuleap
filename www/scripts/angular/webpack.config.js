/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');
var babel_preset_env      = require('babel-preset-env');
var babel_plugin_istanbul = require('babel-plugin-istanbul');

var assets_dir_path = path.resolve(__dirname, './bin/assets');

var babel_preset_env_ie_config = [babel_preset_env, {
    targets: {
        ie: 11
    },
    modules: false
}];

var babel_preset_env_chrome_config = [babel_preset_env, {
    targets: {
        browsers: ['last 2 Chrome versions']
    },
    modules: false,
    useBuiltIns: true,
    shippedProposals: true
}];

var babel_options   = {
    env: {
        watch: {
            presets: [babel_preset_env_ie_config]
        },
        production: {
            presets: [babel_preset_env_ie_config]
        },
        test: {
            presets: [babel_preset_env_chrome_config]
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                [babel_plugin_istanbul.default, {
                    exclude: ['**/*.spec.js']
                }]
            ]
        }
    }
};

var webpack_config = {
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
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = webpack_config;
