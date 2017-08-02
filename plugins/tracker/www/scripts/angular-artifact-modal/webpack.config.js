/* eslint-disable */
var path    = require('path');
var webpack = require('webpack');

module.exports = {
    entry : path.resolve(__dirname, './index.js'),
    // No output, this is only there to make 'npm run test' work !
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in angular-artifact-modal's node_modules
            path.resolve(__dirname, 'node_modules'),
            'node_modules'
        ],
        alias: {
            'angular-tlp': path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/angular-tlp/index.js'),
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
                    { loader: 'ng-cache-loader' }
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
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};
