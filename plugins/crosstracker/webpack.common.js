/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

let entry_points = {
    "cross-tracker": "./scripts/cross-tracker/src/index.js",
};

const colors = ["blue", "green", "grey", "orange", "purple", "red"];
for (const color of colors) {
    entry_points[`style-${color}`] = `./themes/BurningParrot/css/style-${color}.scss`;
    entry_points[
        `style-${color}-condensed`
    ] = `./themes/BurningParrot/css/style-${color}-condensed.scss`;
}

module.exports = [
    {
        entry: entry_points,
        context: __dirname,
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "../../src/www/assets/crosstracker/")
        ),
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                webpack_configurator.rule_scss_loader,
                webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getVueLoaderPlugin(),
            webpack_configurator.getMomentLocalePlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];
