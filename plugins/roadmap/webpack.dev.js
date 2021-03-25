/*
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

const common = require("./webpack.common.js");
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");
const HtmlWebpackPlugin = require("html-webpack-plugin");

module.exports = webpack_configurator.extendDevConfiguration([
    ...common,
    {
        entry: {
            "index-arrows": "./scripts/roadmap-widget/src/index-arrows.ts",
        },
        output: {
            path: __dirname + "/public",
            filename: "index_bundle.js",
        },
        resolve: {
            extensions: [".js", ".ts"],
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(
                    webpack_configurator.babel_options_chrome_firefox
                ),
            ],
        },
        plugins: [
            new HtmlWebpackPlugin(),
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getTypescriptCheckerPlugin(true),
        ],
    },
]);
