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

const config = {
    entry: {
        "children-view": "./src/children-view.ts",
        "create-view": "./src/create-view.ts",
        "edit-view": "./src/edition/edit-view.ts",
        "artifact-links-field": "./src/fields/artifact-links-field.ts",
        "cross-references-fields": "./src/fields/cross-references-fields.ts",
        "list-fields": "./src/fields/list-fields.ts",
        "mass-change": "./src/mass-change/mass-change-view.ts",
    },
    context: __dirname,
    output: webpack_configurator.configureOutput(
        path.resolve(__dirname, "./frontend-assets/"),
        "/assets/trackers/artifact/",
    ),
    externals: {
        ckeditor4: "CKEDITOR",
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
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getManifestPlugin(),
    ],
};

module.exports = [config];
