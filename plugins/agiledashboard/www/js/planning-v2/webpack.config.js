/* eslint-disable */
const path                  = require('path');
const webpack               = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const babel_preset_env      = require('babel-preset-env');
const babel_plugin_istanbul = require('babel-plugin-istanbul').default;

const assets_dir_path = path.resolve(__dirname, './dist');

const babel_preset_env_ie_config = [babel_preset_env, {
    targets: {
        ie: 11
    },
    modules: false
}];

const babel_preset_env_chrome_config = [babel_preset_env, {
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
            presets: [babel_preset_env_chrome_config]
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                [babel_plugin_istanbul, {
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

const path_to_tlp = path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/');

const webpack_config = {
    entry : {
        'planning-v2': './src/app/app.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in planning's node_modules
            path.resolve(__dirname, 'node_modules'),
            'node_modules'
        ],
        alias: {
            'angular-artifact-modal': path.resolve(__dirname, '../../../../tracker/www/scripts/angular-artifact-modal/index.js'),
            'angular-tlp'           : path.join(path_to_tlp, 'angular-tlp/index.js'),
            'tlp-mocks'             : path.join(path_to_tlp, 'mocks/index.js')
        }
    },
    module: {
        rules: [
            babel_rule,
            {
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
} else if (process.env.NODE_ENV === 'watch' || process.env.NODE_ENV === 'test') {
    webpack_config.devtool = 'eval';
}

module.exports = webpack_config;
