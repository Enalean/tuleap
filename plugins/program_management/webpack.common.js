/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */
const path = require("path");
const { webpack_configurator } = require("@tuleap/build-system-configurator");

const entry_points = {
    program_management: "./scripts/program_management/index.ts",
    artifact_additional_action: "./scripts/artifact-additional-action/src/index.ts",
    program_management_admin: "./scripts/admin/src/index.ts",
    "program-management-style": "./themes/program_management.scss",
    "planned-iterations": "./scripts/planned-iterations/index.ts",
    "planned-iterations-style": "./themes/planned-iterations.scss",
};

module.exports = [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "./frontend-assets/"),
            "/assets/program_management/"
        ),
        resolve: {
            extensions: [".js", ".ts", ".vue"],
        },
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                {
                    ...webpack_configurator.rule_po_files,
                    exclude: /program_management\/po\//,
                },
                {
                    test: /\.po$/,
                    include: /program_management\/po\//,
                    use: [{ loader: "json-loader" }, { loader: "easygettext-loader" }],
                },
                webpack_configurator.rule_vue_loader,
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getVueLoaderPlugin(),
            webpack_configurator.getTypescriptCheckerPlugin(true),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];
