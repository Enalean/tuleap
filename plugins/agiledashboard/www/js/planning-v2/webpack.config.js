/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');
var BabelPresetEnv        = require('babel-preset-env');

var babel_plugin_istanbul_path = path.resolve(__dirname, './node_modules/babel-plugin-istanbul');

var assets_dir_path = path.resolve(__dirname, './dist');

var babel_options = {
    presets: [
        [BabelPresetEnv, {
                targets: {
                    ie: 11
                },
                modules: false
        }]
    ],
    env: {
        coverage: {
            plugins: [
                [babel_plugin_istanbul_path, {
                    exclude: [ "**/*.spec.js"]
                }]
            ]
        }
    }
};

var webpack_config = {
    entry : {
        'planning-v2': './src/app/app.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    resolve: {
        modules: [
            'node_modules',
            'vendor',
        ],
        alias: {
            // Needed as long as we use FlamingParrot
            'angular-ui-bootstrap-templates'  : 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            // Bower angular modal dependencies
            'angular-ckeditor'                : 'angular-ckeditor/angular-ckeditor.js',
            'angular-bootstrap-datetimepicker': 'angular-bootstrap-datetimepicker/src/js/datetimepicker.js',
            'angular-ui-select'               : 'angular-ui-select/dist/select.js',
            'angular-filter'                  : 'angular-filter/index.js',
            'angular-base64-upload'           : 'angular-base64-upload/index.js',
            'tuleap-artifact-modal'           : 'artifact-modal/dist/tuleap-artifact-modal.js',
        }
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: [
                    /node_modules/,
                    /vendor/
                ],
                use: [
                    {
                        loader: 'babel-loader',
                        options: babel_options
                    }
                ]
            }, {
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
            merge: true,
            writeToDisk: true
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
} else if (process.env.NODE_ENV === 'watch' || process.env.NODE_ENV === 'test') {
    webpack_config.devtool = 'eval';
}

module.exports = webpack_config;
