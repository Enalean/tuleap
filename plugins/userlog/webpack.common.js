/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
    "user-logging-date-picker": "./scripts/user-logging-date-picker.ts",
};

const colors_burning_parrot = ["orange", "blue", "green", "red", "grey", "purple"];
for (const color of colors_burning_parrot) {
    entry_points[`style-bp-${color}`] = `./themes/BurningParrot/css/style-${color}.scss`;
    entry_points[`style-bp-${color}-condensed`] = `./themes/BurningParrot/css/style-${color}.scss`;
}

module.exports = [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "../../src/www/assets/userlog")
        ),
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(
                    webpack_configurator.babel_options_ie11
                ),
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getTypescriptCheckerPlugin(false),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];
