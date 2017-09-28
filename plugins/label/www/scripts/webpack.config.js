/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, '../assets');

module.exports = {
    entry : {
        'widget-project-labeled-items': './project-labeled-items/src/index.js',
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
            'vue$': 'vue/dist/vue.esm.js'
        }
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: [
                    { loader: 'babel-loader' }
                ]
            }, {
                test: /\.vue$/,
                use: [
                    { loader: 'vue-loader' }
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
    module.exports.plugins = (module.exports.plugins || []).concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.ModuleConcatenationPlugin()
    ])
}
