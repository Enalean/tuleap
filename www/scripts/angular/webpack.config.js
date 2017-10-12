/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, './bin/assets');
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
                test: /\.html$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'ng-cache-loader',
                        query: '-url'
                    }
                ]
            },
            {
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
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = webpack_config;
