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
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");

module.exports = [
    {
        entry: {
            style: "./themes/css/style.scss",
            null: "null_entry",
        },
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "../../src/www/assets/forumml/")
        ),
        module: {
            rules: [webpack_configurator.rule_scss_loader],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
            ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
                "forumml.js": ["./scripts/forumml.js", "./scripts/cc_attach.js"],
            }),
            webpack_configurator.getManifestPlugin(),
        ],
    },
];
