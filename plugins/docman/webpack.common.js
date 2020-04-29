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
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../src/www/assets/docman/")
);

let entry_points = {
    null: "null_entry",
    notifications: "./scripts/notifications.js",
    "default-style": "./themes/default/css/style.scss",
};

const colors = ["blue", "green", "grey", "orange", "purple", "red"];
for (const color of colors) {
    entry_points[`burningparrot-style-${color}`] = `./themes/BurningParrot/css/style-${color}.scss`;
    entry_points[
        `burningparrot-style-${color}-condensed`
    ] = `./themes/BurningParrot/css/style-${color}-condensed.scss`;
}

module.exports = [
    {
        entry: entry_points,
        context,
        output,
        externals: {
            jquery: "jQuery",
        },
        module: {
            rules: [
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
                webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
            ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
                "docman.js": [
                    "./scripts/docman.js",
                    "./scripts/embedded_file.js",
                    "./scripts/ApprovalTableReminder.js",
                ],
            }),
            webpack_configurator.getManifestPlugin(),
        ],
    },
];
