/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/* eslint-disable */
const path                        = require('path');
const webpack                     = require('webpack');
const WebpackAssetsManifest       = require('webpack-assets-manifest');
const babel_preset_env            = require('babel-preset-env');
const VueLoaderOptionsPlugin      = require('vue-loader-options-plugin');
const BabelPluginObjectRestSpread = require('babel-plugin-transform-object-rest-spread');

var assets_dir_path = path.resolve(__dirname, '../assets');

const babel_preset_env_ie_config = [babel_preset_env, {
    targets: {
        ie: 11
    },
    modules: false
}];


const babel_options = {
    env: {
        watch: {
            presets: [babel_preset_env_ie_config]
        },
        production: {
            presets: [babel_preset_env_ie_config]
        }
    },
    plugins: [
        BabelPluginObjectRestSpread
    ]
};

const babel_rule = {
    test: /\.js$/,
    exclude: /node_modules/,
    use: [
        {
            loader: 'babel-loader',
            options: babel_options
        }
    ]
};

const webpack_config = {
    entry : {
        'permission-per-group' : './permissions-per-group/src/index.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js'
    },
    externals: {
        tlp: 'tlp'
    },
    module: {
        rules: [
            babel_rule,
            {
                test: /\.po$/,
                exclude: /node_modules/,
                use: [
                    { loader: 'json-loader' },
                    { loader: 'po-gettext-loader' }
                ]
            }, {
                test: /\.vue$/,
                use: [
                    {
                        loader: 'vue-loader',
                        options: {
                            loaders: {
                                js: 'babel-loader'
                            },
                            esModule: true
                        }
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
        new VueLoaderOptionsPlugin({
            babel: babel_options
        })
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
} else if (process.env.NODE_ENV === 'watch') {
    webpack_config.devtool = 'eval';
}

module.exports = webpack_config;
