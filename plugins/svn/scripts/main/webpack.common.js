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
const { webpack_configurator } = require("@tuleap/build-system-configurator");

const config = {
    entry: {
        svn: "./src/svn.js",
        "svn-admin": "./src/svn-admin.js",
        homepage: "./src/homepage.ts",
        "style-fp": "./themes/FlamingParrot/css/style.scss",
        "style-bp": "./themes/BurningParrot/css/style.scss",
        "global-admin-migrate": "./src/global-admin-migrate.ts",
    },
    context: __dirname,
    output: webpack_configurator.configureOutput(path.resolve(__dirname, "./frontend-assets")),
    externals: {
        codendi: "codendi",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_scss_loader,
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getManifestPlugin(),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

module.exports = [config];
