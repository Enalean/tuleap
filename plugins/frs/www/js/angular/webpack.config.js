const path                  = require('path');
const webpack               = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');

const assets_dir_path = path.resolve(__dirname, '../../assets');

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
        'tuleap-frs': './src/app/app.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    resolve: {
        modules: [
            'node_modules',
            'vendor'
        ],
        alias: {
            'angular-ui-bootstrap-templates': 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            // Shorthand for testing purpose
            'tuleap-frs-module': path.resolve(__dirname, './src/app/app.js')
        }
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|vendor)/,
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
        })
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
