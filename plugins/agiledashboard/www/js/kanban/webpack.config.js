/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');

var assets_dir_path = path.resolve(__dirname, './dist');
module.exports = {
    entry : {
        kanban: './src/app/app.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in kanban's node_modules
            path.resolve(__dirname, 'node_modules'),
            'node_modules',
            'vendor',
        ],
        alias: {
            // We should probably package angular-ui-bootstrap-templates for npm ourselves
            'angular-ui-bootstrap-templates': 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            // Our own components and their dependencies
            'angular-artifact-modal'  : path.resolve(__dirname, '../../../../tracker/www/scripts/angular-artifact-modal/index.js'),
            'cumulative-flow-diagram' : path.resolve(__dirname, '../cumulative-flow-diagram/index.js'),
            'angular-tlp'             : path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/angular-tlp'),
            // Angular artifact modal fixes
            'angular-bootstrap-datetimepicker': 'angular-bootstrap-datetimepicker/src/js/datetimepicker.js',
            'angular-ui-select'               : 'angular-ui-select/dist/select.js',
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
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: false,
            writeToDisk: true
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};
