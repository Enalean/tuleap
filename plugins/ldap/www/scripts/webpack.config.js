/*
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
var path                  = require('path');;
var WebpackAssetsManifest = require('webpack-assets-manifest');
var BabelPresetEnv        = require('babel-preset-env');

var assets_dir_path = path.resolve(__dirname, '../assets');

module.exports = {
    entry : {
        'project-admin-members': './project-admin-members.js',
        'project-admin-ugroups': './project-admin-ugroups/project-admin-ugroups.js'
    },
    output: {
        path    : assets_dir_path,
        filename: '[name]-[chunkhash].js',
    },
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        alias: {
            escaper$: '../../../../src/www/scripts/tuleap/escaper.js'
        }
    },
    module: {
        rules: [
            {
                test: /\.mustache$/,
                use: { loader: 'raw-loader' }
            },
            {
                test: /\.po$/,
                exclude: /node_modules/,
                use: [
                    { loader: 'json-loader' },
                    { loader: 'po-gettext-loader' }
                ]
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                [BabelPresetEnv, {
                                    targets: {
                                        ie: 11
                                    },
                                    modules: false
                                }]
                            ],
                            plugins: [
                                "babel-plugin-transform-object-rest-spread"
                            ]
                        }
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
        })
    ]
};
