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

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = path.resolve(__dirname);
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets"),
    "/assets/hudson_git/",
);

const webpack_config_for_themes = {
    entry: {
        style: "./themes/default/css/style.scss",
        "git-administration": "./scripts/src/git-administration.ts",
    },
    context,
    output,
    externals: {
        jquery: "jQuery",
    },
    module: {
        rules: [
            webpack_configurator.rule_scss_loader,
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        manifest_plugin,
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
};

module.exports = [webpack_config_for_themes];
