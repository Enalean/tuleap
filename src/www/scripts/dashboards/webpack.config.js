/* eslint-disable */
var path                  = require('path');
var WebpackAssetsManifest = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, '../../assets');
module.exports = {
    entry: {
        dashboard: path.resolve(__dirname, 'dashboard.js'),
    },
    output: {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    resolve: {
        modules: [ 'node_modules' ]
    },
    externals: {
        jquery: 'jQuery',
        tlp   : 'tlp'
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true
        })
    ]
};
