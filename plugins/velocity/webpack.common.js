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
const { webpack_configurator } = require("@tuleap/build-system-configurator");

let entry_points = {
    "velocity-chart": "./scripts/velocity-chart/src/index.js",
    "style-fp": "./themes/FlamingParrot/css/style.scss",
    "velocity-style": "./themes/BurningParrot/css/velocity.scss",
};

module.exports = [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(path.resolve(__dirname, "./frontend-assets")),
        resolve: {
            alias: {
                // deduplicate moment that is also used by chart-builder
                moment$: path.resolve(__dirname, "node_modules/moment"),
            },
        },
        module: {
            rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_po_files],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getMomentLocalePlugin(),
        ],
    },
];
