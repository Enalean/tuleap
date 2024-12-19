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

const path = require("node:path");
const { webpack_configurator } = require("@tuleap/build-system-configurator");

module.exports = [
    {
        entry: {
            "tuleap-pullrequest": "./src/app/app.js",
            "pull-requests-style": "./themes/style.scss",
        },
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(path.resolve(__dirname, "./frontend-assets/")),
        externals: {
            tlp: "tlp",
            jquery: "jQuery",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                webpack_configurator.rule_ng_cache_loader,
                webpack_configurator.rule_angular_gettext_loader,
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolve: {
            extensions: [".ts", ".js"],
            alias: {
                // deduplicate angular that is also used by angular-async
                angular$: path.resolve(__dirname, "node_modules/angular"),
                // deduplicate common dependencies
                "@tuleap/date-helper": path.resolve(__dirname, "node_modules/@tuleap/date-helper"),
                "@tuleap/fault": path.resolve(__dirname, "node_modules/@tuleap/fault"),
                "@tuleap/fetch-result": path.resolve(
                    __dirname,
                    "node_modules/@tuleap/fetch-result",
                ),
                "@tuleap/option": path.resolve(__dirname, "node_modules/@tuleap/option"),
                "@tuleap/plugin-pullrequest-constants": path.resolve(
                    __dirname,
                    "node_modules/@tuleap/plugin-pullrequest-constants",
                ),
                "@tuleap/tlp-fetch": path.resolve(__dirname, "node_modules/@tuleap/tlp-fetch"),
                "@tuleap/tlp-relative-date": path.resolve(
                    __dirname,
                    "node_modules/@tuleap/tlp-relative-date",
                ),
                "@tuleap/tooltip": path.resolve(__dirname, "node_modules/@tuleap/tooltip"),
                hybrids: path.resolve(__dirname, "node_modules/hybrids/src/index.js"),
                neverthrow: path.resolve(__dirname, "node_modules/neverthrow"),
            },
        },
    },
];
