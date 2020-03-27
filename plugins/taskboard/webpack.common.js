/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");

const entry_points = {
    taskboard: "./scripts/taskboard/index.ts",
};

const colors = ["blue", "green", "grey", "orange", "purple", "red"];
for (const color of colors) {
    entry_points[`taskboard-${color}`] = `./themes/taskboard-${color}.scss`;
    entry_points[`taskboard-${color}-condensed`] = `./themes/taskboard-${color}-condensed.scss`;
}

module.exports = [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "../../src/www/assets/taskboard/"),
            "/assets/taskboard/"
        ),
        resolve: {
            extensions: [".js", ".ts", ".vue"],
        },
        externals: {
            tlp: "tlp",
            jquery: "jQuery",
        },
        module: {
            rules: [
                {
                    // Transpile only p-retry because it has an object spread, which is unsupported by Edge
                    test: /node_modules\/p-retry\/index\.js$/,
                    use: [
                        {
                            loader: "babel-loader",
                            options: {
                                plugins: ["@babel/plugin-proposal-object-rest-spread"],
                            },
                        },
                    ],
                },
                ...webpack_configurator.configureTypescriptRules(
                    webpack_configurator.babel_options_chrome_firefox
                ),
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
                webpack_configurator.rule_file_loader_images,
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
