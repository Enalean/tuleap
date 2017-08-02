/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');

var assets_dir_path = path.resolve(__dirname, './bin/assets');
module.exports = {
    entry : {
        trafficlights: './src/app/app.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name].js'
    },
    resolve: {
        modules: [
            path.resolve(__dirname, 'node_modules'),
            'node_modules',
            'vendor'
        ],
        alias: {
            'angular-artifact-modal': path.resolve(__dirname, '../../../../tracker/www/scripts/angular-artifact-modal'),
            'angular-tlp'           : path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/angular-tlp'),
            // Bower only deps
            'angular-ui-utils'      : 'angular-ui-utils/unique.js',
            'angular-filter-pack'   : 'angular-filter-pack/dist/angular-filter-pack.js',
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
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};
