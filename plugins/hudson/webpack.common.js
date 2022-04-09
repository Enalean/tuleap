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
const { webpack_configurator } = require("@tuleap/build-system-configurator");
const context = __dirname;
const output = webpack_configurator.configureOutput(path.resolve(__dirname, "./frontend-assets"));

const entry_points = {
    "default-style": "./themes/default/style.scss",
    "test-results-pie": "./scripts/test-results-pie-chart.js",
    hudson_tab: "./scripts/hudson_tab.js",
    "hudson-style": "./themes/BurningParrot/hudson.scss",
};

module.exports = [
    {
        entry: entry_points,
        context,
        output,
        externals: {
            jquery: "jQuery",
        },
        module: {
            rules: [webpack_configurator.rule_po_files, webpack_configurator.rule_scss_loader],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getMomentLocalePlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];
