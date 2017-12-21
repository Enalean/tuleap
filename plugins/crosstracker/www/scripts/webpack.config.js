/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, '../assets');

var babel_preset_env_ie_config = ['babel-preset-env', {
    targets: {
        ie: 11
    },
    modules: false
}];

var babel_preset_env_chrome_config = ['babel-preset-env', {
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
            presets: [babel_preset_env_chrome_config],
            plugins: ['babel-plugin-rewire-exports']
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                'babel-plugin-rewire-exports',
                ['babel-plugin-istanbul', {
                    exclude: ['**/*.spec.js']
                }]
            ]
        }
    }
};

var webpack_config = {
    entry : {
        'cross-tracker': './cross-tracker/src/app/index.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
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
                test: /\.vue$/,
                use: [
                    {
                        loader: 'vue-loader',
                        options: {
                            loaders: {
                                js: 'babel-loader?' + JSON.stringify(babel_options)
                            },
                            esModule: true
                        }
                    }
                ]
            },
            {
                test: /\.mustache$/,
                use: { loader: 'raw-loader' }
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
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
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
} else if (process.env.NODE_ENV === 'test' || process.env.NODE_ENV === 'watch') {
    webpack_config.devtool = 'cheap-eval-source-map';
}

module.exports = webpack_config;
