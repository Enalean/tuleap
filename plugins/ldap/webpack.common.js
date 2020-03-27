/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    path.resolve(__dirname, "../../src/www/assets/ldap")
);

const entry_points = {
    "project-admin-members": "./scripts/project-admin/project-admin-members.js",
    "project-admin-ugroups": "./scripts/project-admin/project-admin-ugroups.js",
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
        context,
        output,
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
                webpack_configurator.rule_mustache_files,
                webpack_configurator.rule_po_files,
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];
