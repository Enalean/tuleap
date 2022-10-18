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

const assets_output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../frontend-assets/pullrequest-create-button")
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const entry_points = {
    "create-pullrequest-button": "./src/index.js",
    "repository-style": "../../themes/repository.scss",
};

module.exports = [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: assets_output,
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
            ],
        },
        plugins: [
            manifest_plugin,
            ...webpack_configurator.getCSSExtractionPlugins(),
            webpack_configurator.getVueLoaderPlugin(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];
