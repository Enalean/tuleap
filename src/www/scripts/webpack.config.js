/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

const loadJsonFile = require("load-json-file");
const WebpackAssetsManifest = require("webpack-assets-manifest");
const path = require("path");
const polyfills_for_fetch = require("../../../tools/utils/scripts/ie11-polyfill-names.js")
    .polyfills_for_fetch;
const webpack_configurator = require("../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../assets");
const manifest_plugin = new WebpackAssetsManifest({
    output: "manifest.json",
    merge: true,
    writeToDisk: true,
    customize(entry) {
        if (entry.key !== "ckeditor.js") {
            return entry;
        }

        return {
            key: entry.key,
            value: `ckeditor-${ckeditor_version}/ckeditor.js`
        };
    }
});

const pkg = loadJsonFile.sync(path.resolve(__dirname, "package-lock.json"));
const ckeditor_version = pkg.dependencies.ckeditor.version;
const webpack_config_for_ckeditor = {
    entry: {
        ckeditor: "./node_modules/ckeditor/ckeditor.js"
    },
    output: webpack_configurator.configureOutput(assets_dir_path),
    plugins: [
        manifest_plugin,
        webpack_configurator.getCopyPlugin([
            {
                from: path.resolve(__dirname, "node_modules/ckeditor"),
                to: path.resolve(__dirname, `../assets/ckeditor-${ckeditor_version}/`),
                toType: "dir"
            }
        ])
    ]
};

const webpack_config_for_dashboards = {
    entry: {
        dashboard: "./dashboards/dashboard.js",
        "widget-project-heartbeat": "./dashboards/widgets/project-heartbeat/index.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        jquery: "jQuery",
        tlp: "tlp"
    },
    module: {
        rules: [webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11)]
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};

const webpack_config_for_flaming_parrot_code = {
    entry: {
        "flamingparrot-with-polyfills": polyfills_for_fetch.concat(["./FlamingParrot/index.js"])
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        jquery: "jQuery",
        tuleap: "tuleap"
    },
    resolve: {
        alias: {
            // keymaster-sequence isn't on npm
            "keymaster-sequence": path.resolve(
                __dirname,
                "./FlamingParrot/keymaster-sequence/keymaster.sequence.min.js"
            ),
            // navbar-history-flamingparrot needs this because TLP is not included in FlamingParrot
            "tlp-fetch": path.resolve(__dirname, "../themes/common/tlp/src/js/fetch-wrapper.js")
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            {
                test: /keymaster\.sequence\.min\.js$/,
                use: "imports-loader?key=keymaster"
            }
        ]
    },
    plugins: [manifest_plugin]
};

const webpack_config_for_burning_parrot_code = {
    entry: {
        "burning-parrot": "./BurningParrot/index.js",
        "project-admin": "./project/admin/index.js",
        "project-admin-ugroups": "./project/admin//project-admin-ugroups.js",
        "site-admin-permission-delegation": "./admin/permission-delegation.js",
        "site-admin-mass-emailing": "./admin/massmail.js",
        "site-admin-most-recent-logins": "./admin/most-recent-logins.js",
        "site-admin-pending-users": "./admin/pending-users.js",
        "site-admin-project-configuration": "./admin/project-configuration.js",
        "site-admin-project-history": "./admin/project-history.js",
        "site-admin-project-list": "./admin/project-list.js",
        "site-admin-system-events": "./admin/system-events.js",
        "site-admin-system-events-admin-homepage": "./admin/system-events-admin-homepage.js",
        "site-admin-system-events-notifications": "./admin/system-events-notifications.js",
        "site-admin-trackers-pending-removal": "./admin/trackers-pending-removal.js",
        "site-admin-user-details": "./admin/userdetails.js",
        "site-admin-generate-pie-charts": "./admin/generate-pie-charts.js",
        "access-denied-error": "./BurningParrot/access-denied-error.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp"
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_mustache_files
        ]
    },
    plugins: [manifest_plugin]
};

const webpack_config_for_vue_components = {
    entry: {
        "news-permissions": "./news/permissions-per-group/index.js",
        "frs-permissions": "./frs/permissions-per-group/index.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp"
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

module.exports = [
    webpack_config_for_ckeditor,
    webpack_config_for_dashboards,
    webpack_config_for_flaming_parrot_code,
    webpack_config_for_burning_parrot_code,
    webpack_config_for_vue_components
];
