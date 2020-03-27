/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

const path = require("path");
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");
const assets_dir_path = path.resolve(__dirname, "../../src/www/assets/document");
const assets_public_path = "/assets/document/";
const MomentTimezoneDataPlugin = require("moment-timezone-data-webpack-plugin");

const entry_points = {
    document: "./scripts/document/index.js",
};

const colors = ["blue", "green", "grey", "orange", "purple", "red"];
for (const color of colors) {
    entry_points[`style-${color}`] = `./themes/BurningParrot/document-${color}.scss`;
    entry_points[
        `style-${color}-condensed`
    ] = `./themes/BurningParrot/document-${color}-condensed.scss`;
}

module.exports = [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
        externals: {
            tlp: "tlp",
        },
        resolve: {
            alias: webpack_configurator.tlp_fetch_alias,
        },
        module: {
            rules: [
                webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getVueLoaderPlugin(),
            webpack_configurator.getMomentLocalePlugin(),
            new MomentTimezoneDataPlugin({
                startYear: 1970,
                endYear: new Date().getFullYear() + 1,
            }),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];
