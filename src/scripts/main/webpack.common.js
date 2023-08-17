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

const fs = require("fs");
// eslint-disable-next-line import/no-extraneous-dependencies
const WebpackAssetsManifest = require("webpack-assets-manifest");
const path = require("path");
const { webpack_configurator, esbuild_target } = require("@tuleap/build-system-configurator");
// Dependency is defined at the root of the repo
// eslint-disable-next-line import/no-extraneous-dependencies
const { ESBuildMinifyPlugin } = require("esbuild-loader");
const context = __dirname;
const assets_dir_path = path.resolve(__dirname, "./frontend-assets");
const output = webpack_configurator.configureOutput(assets_dir_path, "/assets/core/main/");

const pkg = JSON.parse(
    fs.readFileSync(path.resolve(__dirname, "./node_modules/ckeditor4/package.json"))
);
const ckeditor_version = pkg.version;

// Prototype doesn't like to have its "$super" argument mangled due to the fact
// that it checks for its presence during class initialization
const optimization_target_with_prototypejs_support = {
    minimizer: [
        new ESBuildMinifyPlugin({
            target: esbuild_target,
            minifySyntax: true,
            minifyWhitespace: true,
            minifyIdentifiers: false,
            include: [/including-prototypejs/],
        }),
    ],
};

const manifest_plugin = new WebpackAssetsManifest({
    output: "manifest.json",
    merge: true,
    writeToDisk: true,
    apply(manifest) {
        manifest.set("ckeditor.js", `ckeditor-${ckeditor_version}/ckeditor.js`);
    },
});

const webpack_config_for_ckeditor = {
    entry: {},
    context,
    output,
    plugins: [
        manifest_plugin,
        webpack_configurator.getCopyPlugin([
            {
                from: path.resolve(__dirname, "./node_modules/ckeditor4"),
                to: path.resolve(__dirname, `./frontend-assets/ckeditor-${ckeditor_version}/`),
                toType: "dir",
                globOptions: {
                    ignore: [
                        "**/samples/**",
                        "**/.github/**",
                        "**/*.!(js|css|png)",
                        "**/assets/ckeditor4.png",
                        "**/adapters/**",
                    ],
                },
            },
        ]),
    ],
    stats: {
        excludeAssets: [/\/plugins\//, /\/lang\//, /\/skins\//],
    },
};

const webpack_config_for_flaming_parrot_code = {
    entry: {
        "flamingparrot-with-polyfills": "./src/FlamingParrot/index.ts",
        "syntax-highlight": "./src/syntax-highlight/index.ts",
    },
    context,
    output,
    externals: {
        jquery: "jQuery",
        tuleap: "tuleap",
    },
    resolve: {
        extensions: [".ts", ".js"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getTypescriptCheckerPlugin(false)],
};

const webpack_config_for_rich_text_editor = {
    entry: {
        "rich-text-editor-including-prototypejs": "./src/tuleap/textarea_rte.js",
    },
    context,
    output,
    externals: {
        ckeditor4: "CKEDITOR",
        tuleap: "tuleap",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin],
    optimization: optimization_target_with_prototypejs_support,
};

const webpack_config_for_burning_parrot_code = {
    entry: {
        "access-denied-error": "./src/BurningParrot/src/access-denied-error.ts",
        "burning-parrot": "./src/BurningParrot/src/index.ts",
        "dashboards/dashboard": "./src/dashboards/dashboard.js",
        "dashboards/widget-contact-modal": "./src/dashboards/widgets/contact-modal.ts",
        "frs-admin-license-agreement": "./src/frs/admin/license-agreement.ts",
        "project-admin": "./src/project/admin/src/index.ts",
        "project-admin-ugroups": "./src/project/admin/src/project-admin-ugroups.ts",
        "project/project-banner": "./src/project/banner/index.ts",
        "platform/platform-banner": "./src/platform/banner/index.ts",
        "tlp-relative-date": "./src/tuleap/tlp-relative-date-loader.ts",
        "trovecat-admin": "./src/tuleap/trovecat.ts",
        "widget-project-heartbeat": "./src/dashboards/widgets/project-heartbeat/index.ts",
        "browser-deprecation-bp": "./src/browser-deprecation/browser-deprecation-modal-bp.ts",
        "browser-deprecation-fp": "./src/browser-deprecation/browser-deprecation-modal-fp.ts",
        "project/header-background-admin": "./src/project/admin/header-background/admin-index.ts",
        "collect-frontend-errors": "./src/tuleap/collect-frontend-errors.ts",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        tuleap: "tuleap",
        ckeditor4: "CKEDITOR",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_mustache_files,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getTypescriptCheckerPlugin(false),
        webpack_configurator.getMomentLocalePlugin(),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const fat_combined_files = [
        "../../www/scripts/prototype/prototype.js",
        "../../www/scripts/protocheck/protocheck.js",
        "../../www/scripts/scriptaculous/scriptaculous.js",
        "../../www/scripts/scriptaculous/builder.js",
        "../../www/scripts/scriptaculous/effects.js",
        "../../www/scripts/scriptaculous/dragdrop.js",
        "../../www/scripts/scriptaculous/controls.js",
        "../../www/scripts/jquery/jquery-1.9.1.min.js",
        "../../www/scripts/jquery/jquery-ui.min.js",
        "../../www/scripts/jquery/jquery-noconflict.js",
        "../../www/scripts/tuleap/project-history.js",
        "../../www/scripts/bootstrap/bootstrap-dropdown.js",
        "../../www/scripts/bootstrap/bootstrap-button.js",
        "../../www/scripts/bootstrap/bootstrap-modal.js",
        "../../www/scripts/bootstrap/bootstrap-collapse.js",
        "../../www/scripts/bootstrap/bootstrap-tooltip.js",
        "../../www/scripts/bootstrap/bootstrap-tooltip-fix-prototypejs-conflict.js",
        "../../www/scripts/bootstrap/bootstrap-popover.js",
        "../../www/scripts/bootstrap/bootstrap-select/bootstrap-select.js",
        "../../www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js",
        "../../www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.fr.js",
        "../../www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker-fix-prototypejs-conflict.js",
        "../../www/scripts/select2/select2.min.js",
        "../../www/scripts/codendi/common.js",
        "../../www/scripts/codendi/feedback.js",
        "../../www/scripts/codendi/cross_references.js",
        "./node_modules/@tuleap/tooltip/dist/tooltip.umd.cjs",
        "../../www/scripts/codendi/Tooltip-loader.js",
        "../../www/scripts/codendi/Toggler.js",
        "../../www/scripts/codendi/DropDownPanel.js",
        "../../www/scripts/autocomplete.js",
        "../../www/scripts/textboxlist/multiselect.js",
        "../../www/scripts/tablekit/tablekit.js",
        "../../www/scripts/lytebox/lytebox.js",
        "../../www/scripts/lightwindow/lightwindow.js",
        "./node_modules/@tuleap/html-escaper/dist/html-escaper.umd.cjs",
        "../../www/scripts/codendi/Tracker.js",
        "../../www/scripts/codendi/TreeNode.js",
        "../../www/scripts/tuleap/tuleap-modal.js",
        "../../www/scripts/tuleap/datetimepicker.js",
        "../../www/scripts/tuleap/svn.js",
        "../../www/scripts/tuleap/search.js",
        "../../www/scripts/tuleap/tuleap-ckeditor-toolbar.js",
    ],
    subset_combined_files = [
        "../../www/scripts/jquery/jquery-2.1.1.min.js",
        "../../www/scripts/bootstrap/bootstrap-tooltip.js",
        "../../www/scripts/bootstrap/bootstrap-popover.js",
        "../../www/scripts/bootstrap/bootstrap-button.js",
    ],
    subset_combined_flamingparrot_files = [
        "../../www/scripts/bootstrap/bootstrap-dropdown.js",
        "../../www/scripts/bootstrap/bootstrap-modal.js",
        "./node_modules/@tuleap/tooltip/dist/tooltip.umd.cjs",
    ];

const webpack_config_legacy_combined = {
    entry: {},
    context,
    output,
    plugins: [
        ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
            "tuleap-including-prototypejs.js": fat_combined_files,
            "tuleap_subset.js": subset_combined_files,
            "tuleap_subset_flamingparrot.js": subset_combined_files.concat(
                subset_combined_flamingparrot_files
            ),
        }),
        manifest_plugin,
    ],
    optimization: optimization_target_with_prototypejs_support,
};

const theme_entry_points = {
    "common-theme/style": "./node_modules/@tuleap/common-theme/css/style.scss",
    "common-theme/print": "./node_modules/@tuleap/common-theme/css/print.scss",
    "common-theme/project-sidebar": "./node_modules/@tuleap/common-theme/css/project-sidebar.scss",
    "dashboards-style": "./node_modules/@tuleap/burningparrot-theme/css/dashboards/dashboards.scss",
    "account-registration-style":
        "./node_modules/@tuleap/burningparrot-theme/css/account-registration/account-registration.scss",
    "BurningParrot/burning-parrot":
        "./node_modules/@tuleap/burningparrot-theme/css/burning-parrot.scss",
    "homepage-style": "./node_modules/@tuleap/burningparrot-theme/css/homepage/homepage.scss",
};

const project_background_themes = [
    "aerial-water",
    "asphalt-rock",
    "beach-daytime",
    "blue-rain",
    "blue-sand",
    "brown-alpaca",
    "brown-desert",
    "brown-grass",
    "brown-textile",
    "brush-daytime",
    "green-grass",
    "green-leaf",
    "green-trees",
    "led-light",
    "ocean-waves",
    "octopus-black",
    "orange-tulip",
    "purple-building",
    "purple-droplet",
    "purple-textile",
    "snow-mountain",
    "tree-water",
    "white-sheep",
    "wooden-surface",
];
for (const background of project_background_themes) {
    theme_entry_points[
        `project-background/${background}`
    ] = `./node_modules/@tuleap/common-theme/css/project-background/${background}.scss`;
}

const webpack_config_for_burning_parrot_css = {
    entry: theme_entry_points,
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

const webpack_config_for_flaming_parrot_css = {
    entry: {
        "FlamingParrot/style": "./node_modules/@tuleap/flamingparrot-theme/css/style.scss",
        "FlamingParrot/print": "./node_modules/@tuleap/flamingparrot-theme/css/print.scss",
        "syntax-highlight": "./node_modules/@tuleap/common-theme/css/syntax-highlight.scss",
    },
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

module.exports = [
    webpack_config_for_ckeditor,
    webpack_config_legacy_combined,
    webpack_config_for_rich_text_editor,
    webpack_config_for_flaming_parrot_code,
    webpack_config_for_burning_parrot_code,
    webpack_config_for_burning_parrot_css,
    webpack_config_for_flaming_parrot_css,
];
