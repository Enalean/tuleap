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
const polyfills_for_fetch = require("../../../../../tools/utils/scripts/ie11-polyfill-names.js")
    .polyfills_for_fetch;
const webpack_configurator = require("../../../../../tools/utils/scripts/webpack-configurator.js");

let entry_points = {
    "tlp-en_US": polyfills_for_fetch.concat(["dom4", "./src/index.en_US.js"]),
    "tlp-fr_FR": polyfills_for_fetch.concat(["dom4", "./src/index.fr_FR.js"]),
};

const colors = ["orange", "blue", "green", "red", "grey", "purple"];
for (const color of colors) {
    entry_points[`tlp-${color}`] = `./src/scss/tlp-${color}.scss`;
    entry_points[`tlp-${color}-condensed`] = `./src/scss/tlp-${color}-condensed.scss`;
}

const tlp_framework_config = {
    entry: entry_points,
    context: path.resolve(__dirname),
    output: {
        path: path.resolve(__dirname, "dist/"),
        filename: "tlp-[chunkhash].[name].js",
        library: "tlp",
    },
    resolve: {
        alias: {
            select2: "select2/dist/js/select2.full.js",
        },
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_scss_loader,
            webpack_configurator.rule_css_assets,
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getManifestPlugin(),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
};

const tlp_doc_config = {
    entry: {
        style: "./doc/css/main.scss",
        script: "./doc/js/index.js",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(path.resolve(__dirname, "doc/dist/")),
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_scss_loader,
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getManifestPlugin(),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
};

module.exports = [tlp_framework_config, tlp_doc_config];
