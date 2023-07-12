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

const config = {
    entry: {
        "site-admin-mass-emailing": "./src/massmail.ts",
        "site-admin-most-recent-logins": "./src/most-recent-logins.ts",
        "site-admin-pending-users": "./src/pending-users.ts",
        "site-admin-permission-delegation": "./src/permission-delegation.ts",
        "site-admin-project-configuration": "./src/project-configuration.ts",
        "site-admin-project-history": "./src/project-history.ts",
        "site-admin-project-list": "./src/project-list.ts",
        "site-admin-project-widgets": "./src/project-widgets-configuration/index.ts",
        "site-admin-system-events": "./src/system-events.ts",
        "site-admin-system-events-admin-homepage": "./src/system-events-admin-homepage.ts",
        "site-admin-system-events-notifications": "./src/system-events-notifications.ts",
        "site-admin-trackers-pending-removal": "./src/trackers-pending-removal.ts",
        "site-admin-user-details": "./src/userdetails.ts",
        "site-admin-dates-display": "./src/dates-display.ts",
        "site-admin-description-fields": "./src/description-fields.ts",
        "site-admin-password-policy": "./src/password-policy.ts",
        "site-admin-userlist-styles": "./themes/siteadmin-user-list.scss",
    },
    context: __dirname,
    output: webpack_configurator.configureOutput(
        path.resolve(__dirname, "./frontend-assets"),
        "/assets/core/site-admin/"
    ),
    externals: {
        tlp: "tlp",
        ckeditor4: "CKEDITOR",
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
