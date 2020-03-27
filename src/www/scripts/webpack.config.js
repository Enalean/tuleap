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

const loadJsonFile = require("load-json-file");
// eslint-disable-next-line import/no-extraneous-dependencies
const WebpackAssetsManifest = require("webpack-assets-manifest");
// eslint-disable-next-line import/no-extraneous-dependencies
const merge = require("webpack-merge");
const path = require("path");
const polyfills_for_fetch = require("../../../tools/utils/scripts/ie11-polyfill-names.js")
    .polyfills_for_fetch;
const webpack_configurator = require("../../../tools/utils/scripts/webpack-configurator.js");
const webpack_config_for_rich_text_editor = require("./webpack.richtext.js");
const webpack_config_for_vue_components = require("./webpack.vue.js");
const webpack_config_for_vue_components_with_manifest = require("./webpack.vue.with.manifest.js");

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
            value: `ckeditor-${ckeditor_version}/ckeditor.js`,
        };
    },
});

const pkg = loadJsonFile.sync(path.resolve(__dirname, "package-lock.json"));
const ckeditor_version = pkg.dependencies.ckeditor.version;
const webpack_config_for_ckeditor = {
    entry: {
        ckeditor: "./node_modules/ckeditor/ckeditor.js",
    },
    output: webpack_configurator.configureOutput(assets_dir_path),
    plugins: [
        manifest_plugin,
        webpack_configurator.getCopyPlugin([
            {
                from: path.resolve(__dirname, "node_modules/ckeditor"),
                to: path.resolve(__dirname, `../assets/ckeditor-${ckeditor_version}/`),
                toType: "dir",
                ignore: ["**/samples/**", "**/.github/**", "**/*.!(js|css|png)"],
            },
        ]),
    ],
};

const webpack_config_for_dashboards = {
    entry: {
        dashboard: "./dashboards/dashboard.js",
        "widget-project-heartbeat": "./dashboards/widgets/project-heartbeat/index.js",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        jquery: "jQuery",
        tlp: "tlp",
    },
    module: {
        rules: [webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11)],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const webpack_config_for_flaming_parrot_code = {
    entry: {
        "flamingparrot-with-polyfills": polyfills_for_fetch.concat(["./FlamingParrot/index.js"]),
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        jquery: "jQuery",
        tuleap: "tuleap",
    },
    resolve: {
        alias: {
            // keymaster-sequence isn't on npm
            "keymaster-sequence": path.resolve(
                __dirname,
                "./FlamingParrot/keymaster-sequence/keymaster.sequence.min.js"
            ),
            // navbar-history-flamingparrot needs this because TLP is not included in FlamingParrot
            "tlp-fetch": path.resolve(__dirname, "../themes/common/tlp/src/js/fetch-wrapper.js"),
        },
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            {
                test: /keymaster\.sequence\.min\.js$/,
                use: "imports-loader?key=keymaster",
            },
        ],
    },
    plugins: [manifest_plugin],
};

const webpack_config_for_burning_parrot_code = {
    entry: {
        "burning-parrot": "./BurningParrot/index.js",
        "project-admin": "./project/admin/index.js",
        "project-admin-ugroups": "./project/admin/project-admin-ugroups.js",
        "site-admin-permission-delegation": "./admin/permission-delegation.js",
        "site-admin-mass-emailing": "./admin/massmail.js",
        "site-admin-most-recent-logins": "./admin/most-recent-logins.js",
        "site-admin-pending-users": "./admin/pending-users.js",
        "site-admin-project-configuration": "./admin/project-configuration.js",
        "site-admin-project-history": "./admin/project-history.js",
        "site-admin-project-list": "./admin/project-list.js",
        "site-admin-project-widgets": "./admin/project-widgets-configuration/index.ts",
        "site-admin-system-events": "./admin/system-events.js",
        "site-admin-system-events-admin-homepage": "./admin/system-events-admin-homepage.js",
        "site-admin-system-events-notifications": "./admin/system-events-notifications.js",
        "site-admin-trackers-pending-removal": "./admin/trackers-pending-removal.js",
        "site-admin-user-details": "./admin/userdetails.js",
        "site-admin-generate-pie-charts": "./admin/generate-pie-charts.js",
        "access-denied-error": "./BurningParrot/access-denied-error.js",
        "trovecat-admin": "./tuleap/trovecat.js",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
        tuleap: "tuleap",
        ckeditor: "CKEDITOR",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_mustache_files,
        ],
    },
    plugins: [manifest_plugin],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const webpack_config_for_project_banner = {
    entry: {
        "project-banner-bp": "./project/banner/index-bp.ts",
        "project-banner-fp": "./project/banner/index-fp.ts",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
        ],
    },
    plugins: [webpack_configurator.getTypescriptCheckerPlugin(false)],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const webpack_config_for_frs_admin = {
    entry: {
        "frs-admin-license-agreement": "./frs/admin/license-agreement.js",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tuleap: "tuleap",
        ckeditor: "CKEDITOR",
    },
    module: {
        rules: [webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11)],
    },
};

const webpack_config_for_project_registration_modal = {
    entry: {
        "project-registration-creation": "./project/registration/index-for-modal.ts",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(
        assets_dir_path + "/project-registration/creation/scripts/"
    ),
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_mustache_files,
        ],
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(false),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const webpack_config_for_account = {
    entry: {
        "preferences-nav": "./account/preferences-nav.ts",
        "keys-tokens": "./account/keys-tokens.ts",
        appearance: "./account/appearance.ts",
        security: "./account/security.ts",
        avatar: "./account/avatar.ts",
        timezone: "./account/timezone.ts",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path + "/account/scripts/"),
    externals: {
        tlp: "tlp",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
        ],
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(false),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const fat_combined_files = [
        "./prototype/prototype.js",
        "./protocheck/protocheck.js",
        "./scriptaculous/scriptaculous.js",
        "./scriptaculous/builder.js",
        "./scriptaculous/effects.js",
        "./scriptaculous/dragdrop.js",
        "./scriptaculous/controls.js",
        "./scriptaculous/slider.js",
        "./jquery/jquery-1.9.1.min.js",
        "./jquery/jquery-ui.min.js",
        "./jquery/jquery-noconflict.js",
        "./tuleap/project-history.js",
        "./bootstrap/bootstrap-dropdown.js",
        "./bootstrap/bootstrap-button.js",
        "./bootstrap/bootstrap-modal.js",
        "./bootstrap/bootstrap-collapse.js",
        "./bootstrap/bootstrap-tooltip.js",
        "./bootstrap/bootstrap-tooltip-fix-prototypejs-conflict.js",
        "./bootstrap/bootstrap-popover.js",
        "./bootstrap/bootstrap-select/bootstrap-select.js",
        "./bootstrap/bootstrap-tour/bootstrap-tour.min.js",
        "./bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js",
        "./bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.fr.js",
        "./bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker-fix-prototypejs-conflict.js",
        "./jscrollpane/jquery.mousewheel.js",
        "./jscrollpane/jquery.jscrollpane.min.js",
        "./select2/select2.min.js",
        "./vendor/at/js/caret.min.js",
        "./vendor/at/js/atwho.min.js",
        "./viewportchecker/viewport-checker.js",
        "./clamp.js",
        "./codendi/common.js",
        "./tuleap/massmail_initialize_ckeditor.js",
        "./tuleap/get-style-class-property.js",
        "./tuleap/listFilter.js",
        "./codendi/feedback.js",
        "./codendi/CreateProject.js",
        "./codendi/cross_references.js",
        "./codendi/Tooltip.js",
        "./codendi/Tooltip-loader.js",
        "./codendi/Toggler.js",
        "./codendi/DropDownPanel.js",
        "./autocomplete.js",
        "./textboxlist/multiselect.js",
        "./tablekit/tablekit.js",
        "./lytebox/lytebox.js",
        "./lightwindow/lightwindow.js",
        "./tuleap/escaper.js",
        "./codendi/Tracker.js",
        "./codendi/TreeNode.js",
        "./tuleap/tuleap-modal.js",
        "./tuleap/tuleap-tours.js",
        "./tuleap/tuleap-standard-homepage.js",
        "./tuleap/datetimepicker.js",
        "./tuleap/svn.js",
        "./tuleap/search.js",
        "./tuleap/tuleap-mention.js",
        "./tuleap/project-privacy-tooltip.js",
        "./tuleap/massmail_project_members.js",
        "./tuleap/tuleap-ckeditor-toolbar.js",
    ],
    subset_combined_files = [
        "./jquery/jquery-2.1.1.min.js",
        "./bootstrap/bootstrap-tooltip.js",
        "./bootstrap/bootstrap-popover.js",
        "./bootstrap/bootstrap-button.js",
        "./tuleap/project-privacy-tooltip.js",
    ],
    subset_combined_flamingparrot_files = [
        "./bootstrap/bootstrap-dropdown.js",
        "./bootstrap/bootstrap-modal.js",
        "./bootstrap/bootstrap-tour/bootstrap-tour.min.js",
        "./jscrollpane/jquery.mousewheel.js",
        "./jscrollpane/jquery.jscrollpane.min.js",
        "./tuleap/tuleap-tours.js",
        "./tuleap/listFilter.js",
        "./codendi/Tooltip.js",
    ];

const webpack_config_legacy_combined = {
    entry: {
        null: "null_entry",
    },
    output: webpack_configurator.configureOutput(assets_dir_path),
    plugins: [
        ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
            "tuleap.js": fat_combined_files,
            "tuleap_subset.js": subset_combined_files,
            "tuleap_subset_flamingparrot.js": subset_combined_files.concat(
                subset_combined_flamingparrot_files
            ),
        }),
        manifest_plugin,
    ],
};

const configs_with_manifest = [
    webpack_config_for_vue_components,
    webpack_config_for_rich_text_editor,
    webpack_config_for_project_banner,
    webpack_config_for_frs_admin,
].map((config) =>
    merge(config, {
        plugins: [manifest_plugin],
    })
);

module.exports = [
    webpack_config_for_vue_components_with_manifest,
    webpack_config_for_ckeditor,
    webpack_config_for_dashboards,
    webpack_config_legacy_combined,
    webpack_config_for_flaming_parrot_code,
    webpack_config_for_burning_parrot_code,
    ...configs_with_manifest,
    webpack_config_for_project_registration_modal,
    webpack_config_for_account,
];
