/* eslint-disable */
var path    = require('path');
var webpack = require('webpack');

module.exports = {
    entry : path.resolve(__dirname, './index.js'),
    // No output, this is only there to make 'npm run test' work !
    resolve: {
        modules: [ 'node_modules', 'vendor' ],
        alias: {
            'angular-ui-bootstrap-templates'  : 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            'angular-bootstrap-datetimepicker': 'angular-bootstrap-datetimepicker/src/js/datetimepicker.js',
            'angular-ui-select'               : 'angular-ui-select/dist/select.js',
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
