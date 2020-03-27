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

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = path.resolve(__dirname);
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../src/www/assets/git"),
    "/assets/git/"
);

const webpack_config_for_vue = {
    entry: {
        "permission-per-group": "./scripts/permissions-per-group/src/index.js",
        "repositories-list": "./scripts/repositories/src/index.js",
        repository: "./scripts/repository/src/index.js",
        "repository-blob": [
            "./scripts/repository/file/syntax-highlight.js",
            "./scripts/repository/file/line-highlight.js",
        ],
    },
    context,
    output,
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

const webpack_config_for_vanilla = {
    entry: {
        "siteadmin-gitolite": "./scripts/siteadmin/gitolite.js",
        "siteadmin-gerrit": "./scripts/siteadmin/gerrit/index.js",
        "siteadmin-mirror": "./scripts/siteadmin/mirror/index.js",
        "repo-admin-notifications": "./scripts/admin-notifications.js",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        tuleap: "tuleap",
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const webpack_config_for_legacy_scripts = {
    entry: {
        null: "null_entry",
    },
    context,
    output,
    externals: {
        tuleap: "tuleap",
    },
    plugins: [
        ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
            "git.js": [
                "./scripts/git.js",
                "./scripts/mass-update.js",
                "./scripts/webhooks.js",
                "./scripts/permissions.js",
            ],
        }),
        manifest_plugin,
    ],
};

const entry_points = {
    default: "./themes/default/css/style.scss",
    "syntax-highlight": "./themes/BurningParrot/syntax-highlight.scss",
};

const colors = ["blue", "green", "grey", "orange", "purple", "red"];
for (const color of colors) {
    entry_points[`bp-style-${color}`] = `./themes/BurningParrot/git-${color}.scss`;
    entry_points[
        `bp-style-${color}-condensed`
    ] = `./themes/BurningParrot/git-${color}-condensed.scss`;
    entry_points[`bp-style-siteadmin-${color}`] = `./themes/BurningParrot/site-admin-${color}.scss`;
    entry_points[
        `bp-style-siteadmin-${color}-condensed`
    ] = `./themes/BurningParrot/site-admin-${color}-condensed.scss`;
}

const webpack_config_for_themes = {
    entry: entry_points,
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

module.exports = [
    webpack_config_for_vue,
    webpack_config_for_vanilla,
    webpack_config_for_legacy_scripts,
    webpack_config_for_themes,
];
