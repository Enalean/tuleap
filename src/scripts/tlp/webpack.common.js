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

const path = require("node:path");
const { webpack_configurator } = require("@tuleap/build-system-configurator");

let entry_points = {
    tlp: "./src/scss/tlp.scss",
    // DO NOT add new entrypoints unless it's for a new TLP locale. TLP is exported as a "library". If you add another
    // entrypoint, all scripts that depend on TLP will try to access "select2" or "createModal" from your file
    // (and they will fail).
    "tlp-en_US": "./src/index.en_US.ts",
    "tlp-fr_FR": "./src/index.fr_FR.ts",
};

const tlp_colors = ["orange", "blue", "green", "red", "grey", "purple"];
for (const color of tlp_colors) {
    entry_points[`tlp-vars-${color}`] = `./src/scss/tlp-vars-${color}.scss`;
    entry_points[`tlp-vars-${color}-condensed`] = `./src/scss/tlp-vars-${color}-condensed.scss`;
}

const config = {
    entry: entry_points,
    context: __dirname,
    output: {
        path: path.resolve(__dirname, "./frontend-assets"),
        filename: "tlp-[chunkhash].[name].js",
        library: "tlp",
    },
    resolve: {
        extensions: [".js", ".ts"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
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

module.exports = [config];
