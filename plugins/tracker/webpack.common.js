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

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets/"),
    "/assets/trackers/",
);

const config_for_flaming_parrot = {
    entry: {
        "modal-v2": "./scripts/modal-v2/modal-in-place.js",
    },
    context,
    output,
    externals: {
        ckeditor4: "CKEDITOR",
        codendi: "codendi",
        jquery: "jQuery",
    },
    resolve: {
        extensions: [".js"],
    },
    plugins: [manifest_plugin],
};

let entry_points = {
    "style-fp": "./themes/FlamingParrot/css/style.scss",
    print: "./themes/default/css/print.scss",
    "dependencies-matrix": "./themes/FlamingParrot/css/dependencies-matrix.scss",
    "tracker-bp": "./themes/BurningParrot/css/tracker.scss",
};

const config_for_themes = {
    entry: entry_points,
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

module.exports = [config_for_flaming_parrot, config_for_themes];
