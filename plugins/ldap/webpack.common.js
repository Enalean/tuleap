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

const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets"),
    "/assets/ldap/",
);

const entry_points = {
    "project-admin-members": "./scripts/project-admin/project-admin-members.js",
    "project-admin-ugroups": "./scripts/project-admin/project-admin-ugroups.js",
    "ldap-style": "./themes/css/ldap.scss",
};

module.exports = [
    {
        entry: entry_points,
        context,
        output,
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                webpack_configurator.rule_mustache_files,
                webpack_configurator.rule_po_files,
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];
