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
            'angular'                       : 'angular/index.js',
            'angular-animate'               : 'angular-animate/index.js',
            'angular-gettext'               : 'angular-gettext/dist/angular-gettext.js',
            'angular-jwt'                   : 'angular-jwt/dist/angular-jwt.js',
            'angular-locker'                : 'angular-locker/src/angular-locker.js',
            'angular-moment'                : 'angular-moment/angular-moment.js',
            'angular-sanitize'              : 'angular-sanitize/index.js',
            'angular-socket-io'             : 'angular-socket-io/socket.js',
            'angular-ui-bootstrap-templates': 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            'angular-ui-router'             : 'angular-ui-router/release/angular-ui-router.js',
            'angular-ui-tree'               : 'angular-ui-tree/dist/angular-ui-tree.js',
            'dragular'                      : 'dragular/dist/dragular.js',
            'lodash'                        : 'lodash/dist/lodash.js',
            'moment'                        : 'moment/moment.js',
            'ng-scrollbar'                  : 'ng-scrollbar/dist/ng-scrollbar.js',
            'restangular'                   : 'restangular/dist/restangular.js',
            'socket.io-client'              : 'socket.io-client/socket.io.js',
            'striptags'                     : 'striptags/striptags.js',
            // Modal deps should be required by modal
            'angular-ckeditor'                : 'angular-ckeditor/angular-ckeditor.js',
            'angular-bootstrap-datetimepicker': 'angular-bootstrap-datetimepicker/src/js/datetimepicker.js',
            'angular-ui-select'               : 'angular-ui-select/dist/select.js',
            'angular-filter'                  : 'angular-filter/index.js',
            'angular-base64-upload'           : 'angular-base64-upload/index.js',
            'tuleap-artifact-modal'           : 'artifact-modal/dist/tuleap-artifact-modal.js',
            // Our own components and their dependencies
            'cumulative-chart-factory': path.resolve(__dirname, '../cumulative-chart-factory.js'),
            'd3'                      : 'd3/build/d3.node.js',
            // Test
            'angular-mocks'  : 'angular-mocks/ngMock.js'
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
