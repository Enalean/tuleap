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

const webpack = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const { VueLoaderPlugin } = require('vue-loader');
const rule_configurations = require('./webpack-rule-configs.js');
const aliases = require('./webpack-aliases.js');

function getManifestPlugin() {
    return new WebpackAssetsManifest({
        output     : 'manifest.json',
        merge      : true,
        writeToDisk: true
    });
}

function getMomentLocalePlugin() {
    // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
    return new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, /fr/);
}

function configureOutput(assets_dir_path) {
    return {
        path: assets_dir_path,
        filename: '[name]-[chunkhash].js'
    };
}

function getVueLoaderPlugin() {
    return new VueLoaderPlugin();
}

const configurator = {
    getManifestPlugin,
    getMomentLocalePlugin,
    getVueLoaderPlugin,
    configureOutput
};
Object.assign(configurator, rule_configurations, aliases);

module.exports = configurator;
