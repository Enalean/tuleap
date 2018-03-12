/* eslint-disable */
const path                  = require('path');
const webpack               = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');

const assets_dir_path = path.resolve(__dirname, '../assets');

const babel_options = {
    presets: [
        ["babel-preset-env", {
            targets: {
                ie: 11
            },
            modules: false
        }],
    ],
    env: {
        coverage: {
            plugins: [
                ["babel-plugin-istanbul", {
                    exclude: [ "**/*.spec.js"]
                }]
            ]
        }
    }
};

const webpack_config = {
    entry : {
        'tuleap-pullrequest': './src/app/app.js',
        'move-button-back'  : './move-button-back.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    resolve: {
        modules: [
            'node_modules',
            'bower_components'
        ],
        alias: {
            'tuleap-pullrequest-module'     : path.resolve(__dirname, './src/app/app.js'),
            'angular-ui-bootstrap-templates': 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            'angular-ui-select'             : 'ui-select/dist/select.js'
        }
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
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
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins.push(
        new webpack.optimize.ModuleConcatenationPlugin()
    );
} else if (process.env.NODE_ENV === "watch") {
    webpack_config.module.rules.push({
        test: /src.*\.(js|html)$/,
        loader: 'angular-gettext-extract-loader?pofile=po/template.pot'
    });
}

module.exports = webpack_config;
