/* eslint-disable */
var path = require('path');
module.exports = {
    entry : path.resolve(__dirname, './src/app/app.js'),
    output: {
        path    : path.resolve(__dirname, './bin/assets'),
        filename: 'kanban.js'
    },
    resolve: {
        modules: [
            'node_modules',
            'vendor',
            path.resolve(__dirname, 'node_modules'),
        ],
        alias: {
            // We should probably package angular-ui-bootstrap-templates for npm ourselves
            'angular-ui-bootstrap-templates': 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            // Modal deps should be required by modal
            'angular-ckeditor'                : 'angular-ckeditor/angular-ckeditor.js',
            'angular-bootstrap-datetimepicker': 'angular-bootstrap-datetimepicker/src/js/datetimepicker.js',
            'angular-ui-select'               : 'angular-ui-select/dist/select.js',
            'angular-filter'                  : 'angular-filter/index.js',
            'angular-base64-upload'           : 'angular-base64-upload/index.js',
            'tuleap-artifact-modal'           : 'artifact-modal/dist/tuleap-artifact-modal.js',
            // Our own components and their dependencies
            'cumulative-chart-factory': path.resolve(__dirname, '../cumulative-chart-factory.js'),
        }
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
                use: [
                    {
                        loader: 'angular-gettext-loader',
                        query: 'browserify=true'
                    }
                ]
            }
        ]
    }
};
