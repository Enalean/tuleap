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
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../src/www/assets/trackers"),
    "/assets/trackers/"
);

const webpack_config_for_burndown_chart = {
    entry: {
        "burndown-chart": "./scripts/burndown-chart/src/burndown-chart.js",
    },
    context,
    output,
    resolve: {
        alias: {
            "charts-builders": path.resolve(__dirname, "../../src/scripts/charts-builders/"),
            "d3-array$": path.resolve(__dirname, "node_modules/d3-array"),
            "d3-scale$": path.resolve(__dirname, "node_modules/d3-scale"),
            "d3-axis$": path.resolve(__dirname, "node_modules/d3-axis"),
        },
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const webpack_config_for_vue = {
    entry: {
        "admin-nature": "./scripts/admin-nature.js",
        "global-admin": "./scripts/global-admin.js",
        "tracker-report-expert-mode": "./scripts/report/index.js",
        "tracker-permissions-per-group": "./scripts/permissions-per-group/src/index.js",
        "tracker-workflow-transitions": "./scripts/workflow-transitions/src/index.js",
        MoveArtifactModal: "./scripts/artifact-action-buttons/src/index.js",
        TrackerAdminFields: "./scripts/TrackerAdminFields.js",
        "tracker-semantic-timeframe-option-selector":
            "./scripts/semantic-timeframe-option-selector",
    },
    context,
    output,
    externals: {
        codendi: "codendi",
        jquery: "jQuery",
        tlp: "tlp",
    },
    resolve: {
        alias: webpack_configurator.tlp_fetch_alias,
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

const webpack_for_vue_plus_typescript = {
    entry: {
        "tracker-creation": "./scripts/tracker-creation/index.ts",
        "tracker-creation-success": "./scripts/tracker-creation-success-modal/index.ts",
    },
    context,
    output,
    resolve: {
        extensions: [".js", ".ts", ".vue"],
    },
    externals: {
        tlp: "tlp",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(true),
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

const config_for_legacy_scripts = {
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
            "tracker.js": [
                "./scripts/legacy/TrackerTemplateSelector.js",
                "./scripts/legacy/TrackerCheckUgroupConsistency.js",
                "./scripts/legacy/TrackerReports.js",
                "./scripts/legacy/TrackerEmailCopyPaste.js",
                "./scripts/legacy/TrackerReportsSaveAsModal.js",
                "./scripts/legacy/TrackerBinds.js",
                "./scripts/legacy/ReorderColumns.js",
                "./scripts/legacy/TrackerTextboxLists.js",
                "./scripts/legacy/TrackerAdminFieldWorkflow.js",
                "./scripts/legacy/TrackerArtifact.js",
                "./scripts/legacy/TrackerArtifactEmailActions.js",
                "./scripts/legacy/TrackerArtifactLink.js",
                "./scripts/legacy/LoadTrackerArtifactLink.js",
                "./scripts/legacy/TrackerCreate.js",
                "./scripts/legacy/TrackerFormElementFieldPermissions.js",
                "./scripts/legacy/TrackerDateReminderForms.js",
                "./scripts/legacy/TrackerTriggers.js",
                "./scripts/legacy/SubmissionKeeper.js",
                "./scripts/legacy/TrackerFieldDependencies.js",
                "./scripts/legacy/TrackerRichTextEditor.js",
                "./scripts/legacy/artifactChildren.js",
                "./scripts/legacy/load-artifactChildren.js",
                "./scripts/legacy/modal-in-place.js",
                "./scripts/legacy/TrackerArtifactEditionSwitcher.js",
                "./scripts/legacy/FixAggregatesHeaderHeight.js",
                "./scripts/legacy/TrackerSettings.js",
                "./scripts/legacy/TrackerCollapseFieldset.js",
                "./scripts/legacy/CopyArtifact.js",
                "./scripts/legacy/tracker-report-nature-column.js",
                "./scripts/legacy/tracker-admin-notifications.js",
                "./scripts/legacy/tracker-admin-notifications-popover.js",
                "./scripts/legacy/tracker-webhooks.js",
            ],
        }),
        manifest_plugin,
    ],
};

let entry_points = {
    "style-fp": "./themes/FlamingParrot/css/style.scss",
    print: "./themes/default/css/print.scss",
    "burndown-chart": "./themes/burndown-chart.scss",
    colorpicker: "./themes/FlamingParrot/css/colorpicker.scss",
};

const colors_burning_parrot = ["orange", "blue", "green", "red", "grey", "purple"];
for (const color of colors_burning_parrot) {
    entry_points[`tracker-bp-${color}`] = `./themes/BurningParrot/css/style-${color}.scss`;
    entry_points[
        `tracker-bp-${color}-condensed`
    ] = `./themes/BurningParrot/css/style-${color}-condensed.scss`;
    entry_points[`workflow-${color}`] = `./themes/BurningParrot/css/workflow-${color}.scss`;
    entry_points[
        `workflow-${color}-condensed`
    ] = `./themes/BurningParrot/css/workflow-${color}-condensed.scss`;

    entry_points[
        `tracker-creation-${color}`
    ] = `./themes/BurningParrot/css/tracker-creation/tracker-creation-${color}.scss`;
    entry_points[
        `tracker-creation-${color}-condensed`
    ] = `./themes/BurningParrot/css/tracker-creation/tracker-creation-${color}-condensed.scss`;
}

const config_for_themes = {
    entry: entry_points,
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

module.exports = [
    webpack_config_for_burndown_chart,
    webpack_config_for_vue,
    webpack_for_vue_plus_typescript,
    config_for_legacy_scripts,
    config_for_themes,
];
