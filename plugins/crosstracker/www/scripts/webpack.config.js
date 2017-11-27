/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');
var BabelPresetEnv        = require('babel-preset-env');

var assets_dir_path = path.resolve(__dirname, '../assets');

var babel_options = {
    presets: [
        ["babel-preset-env", {
            targets: {
                ie: 11
            },
            modules: false
        }]
    ]
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
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
} else if (process.env.NODE_ENV === 'watch') {
    webpack_config.devtool = 'eval';
}

module.exports = webpack_config;
