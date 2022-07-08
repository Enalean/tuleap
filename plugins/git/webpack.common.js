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

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = path.resolve(__dirname);
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets"),
    "/assets/git/"
);

const webpack_config_for_vue3 = require("./scripts/artifact-create-branch-action/webpack.common");
webpack_config_for_vue3.output = output;
webpack_config_for_vue3.plugins.push(manifest_plugin);

const webpack_config_for_vue = {
    entry: {
        "permission-per-group": "./scripts/permissions-per-group/index.ts",
        "repositories-list": "./scripts/repositories/index.ts",
        repository: "./scripts/repository/src/index.ts",
        "line-highlight": "./scripts/repository/file/line-highlight.ts",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getTypescriptCheckerPlugin(true),
        webpack_configurator.getVueLoaderPlugin(),
    ],
    resolve: {
        extensions: [".ts", ".js", ".vue"],
        alias: {
            vue: path.resolve(__dirname, "node_modules", "vue"),
        },
    },
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

const webpack_config_for_vanilla = {
    entry: {
        "siteadmin-gitolite": "./scripts/siteadmin/gitolite.ts",
        "siteadmin-gerrit": "./scripts/siteadmin/gerrit/index.ts",
        "siteadmin-mirror": "./scripts/siteadmin/mirror/index.ts",
        "repo-admin-notifications": "./scripts/admin-notifications.js",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        jquery: "jQuery",
    },
    module: {
        rules: [
            webpack_configurator.rule_po_files,
            ...webpack_configurator.configureTypescriptRules(),
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getMomentLocalePlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(false),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const webpack_config_for_legacy_scripts = {
    entry: {},
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
    "bp-style": "./themes/BurningParrot/git.scss",
    "bp-style-siteadmin": "./themes/BurningParrot/site-admin/git.scss",
};

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
    webpack_config_for_vue3,
];
