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
const { VueLoaderPlugin } = require("vue-loader");
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets/"),
    "/assets/testplan/"
);

const entry = {
    testplan: "./scripts/test-plan/index.ts",
    "testplan-style": "./themes/testplan.scss",
};

module.exports = [
    {
        entry,
        context,
        output,
        resolve: {
            extensions: [".js", ".ts", ".vue"],
            alias: {
                docx: path.resolve(__dirname, "node_modules", "docx"),
            },
        },
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                {
                    test: /\.vue$/,
                    exclude: /node_modules/,
                    loader: "vue-loader",
                },
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            require("@tuleap/po-gettext-plugin").default.webpack(),
            new VueLoaderPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];
