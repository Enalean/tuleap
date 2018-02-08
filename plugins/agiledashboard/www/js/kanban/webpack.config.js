/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');
var WebpackAssetsManifest = require('webpack-assets-manifest');
var babel_preset_env      = require('babel-preset-env');
var babel_plugin_istanbul = require('babel-plugin-istanbul');

var manifest_data   = Object.create(null);
var assets_dir_path = path.resolve(__dirname, './dist');

var babel_preset_env_ie_config = [babel_preset_env, {
    targets: {
        ie: 11
    },
    modules: false
}];

var babel_preset_env_chrome_config = [babel_preset_env, {
    targets: {
        browsers: ['last 2 Chrome versions']
    },
    modules: false,
    useBuiltIns: true,
    shippedProposals: true
}];

var babel_options   = {
    env: {
        watch: {
            presets: [babel_preset_env_ie_config]
        },
        production: {
            presets: [babel_preset_env_ie_config]
        },
        test: {
            presets: [babel_preset_env_chrome_config]
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                [babel_plugin_istanbul.default, {
                    exclude: ['**/*.spec.js']
                }]
            ]
        }
    }
};

var webpack_config_for_kanban = {
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
            'node_modules'
        ],
        alias: {
            // Our own components and their dependencies
            'angular-artifact-modal' : path.resolve(__dirname, '../../../../tracker/www/scripts/angular-artifact-modal/index.js'),
            'cumulative-flow-diagram': path.resolve(__dirname, '../cumulative-flow-diagram/index.js'),
            'angular-tlp'            : path.resolve(__dirname, '../../../../../src/www/themes/common/tlp/angular-tlp'),
            'card-fields'            : path.resolve(__dirname, '../card-fields')
        }
    },
    externals: {
        tlp:     'tlp',
        angular: 'angular'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
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
                    {
                        loader: 'ng-cache-loader',
                        query: '-url'
                    }
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
            assets: manifest_data
        }),
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};

var webpack_config_for_angular = {
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
            assets: manifest_data,
            merge: true,
            writeToDisk: true
        })
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config_for_kanban.plugins = webpack_config_for_kanban.plugins.concat([
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
}

module.exports = [
    webpack_config_for_kanban,
    webpack_config_for_angular
];
