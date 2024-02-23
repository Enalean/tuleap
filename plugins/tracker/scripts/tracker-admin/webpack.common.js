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
const { webpack_configurator } = require("@tuleap/build-system-configurator");

module.exports = [
    {
        entry: {
            "done-semantic": "./src/semantics/status/done-picker.ts",
            "field-permissions": "./src/field-permissions.ts",
            "progress-semantic": "./src/semantics/progress/admin-selectors.ts",
            "semantics-homepage": "./src/semantics/homepage.ts",
            "status-semantic": "./src/semantics/status/status-picker.ts",
            TrackerAdminFields: "./src/TrackerAdminFields.js",
            colorpicker: "./themes/colorpicker.scss",
            notifications: "./src/index.js",
        },
        context: __dirname,
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "./frontend-assets/"),
            "/assets/trackers/tracker-admin",
        ),
        externals: {
            codendi: "codendi",
            jquery: "jQuery",
        },
        resolve: {
            extensions: [".js", ".ts"],
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                webpack_configurator.rule_po_files,
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];
