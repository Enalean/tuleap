/* eslint-disable */
var path                        = require('path');
var WebpackAssetsManifest       = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, './dist');
module.exports = {
    entry : {
        angular: 'angular'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true
        })
    ]
};
