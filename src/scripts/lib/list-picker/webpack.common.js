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
const webpack_configurator = require("../../../../tools/utils/scripts/webpack-configurator.js");
const FixStyleOnlyEntriesPlugin = require("../../../../node_modules/webpack-fix-style-only-entries");
const MiniCssExtractPlugin = require("../../../../node_modules/mini-css-extract-plugin");

const context = __dirname;

const webpack_config_list_picker = {
    entry: {
        "list-picker": "./src/index.ts",
        "list-picker-style": "./themes/style.scss",
    },
    context,
    output: {
        path: path.join(context, "./dist/"),
        library: "ListPicker",
        libraryTarget: "umd",
    },
    resolve: {
        extensions: [".ts", ".js"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptLibraryRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_scss_loader,
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        new FixStyleOnlyEntriesPlugin({
            extensions: ["scss", "css"],
            silent: true,
        }),
        new MiniCssExtractPlugin(),
    ],
};

module.exports = [webpack_config_list_picker];
