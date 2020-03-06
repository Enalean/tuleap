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
const webpack_configurator = require("../../../tools/utils/scripts/webpack-configurator.js");

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = path.resolve(__dirname);
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../www/assets"),
    "/plugins/tracker/assets/"
);

const webpack_config_for_burndown_chart = {
    entry: {
        "burndown-chart": "./burndown-chart/src/burndown-chart.js"
    },
    context,
    output,
    resolve: {
        alias: {
            "charts-builders": path.resolve(__dirname, "../../../src/www/scripts/charts-builders/"),
            "d3-array$": path.resolve(__dirname, "node_modules/d3-array"),
            "d3-scale$": path.resolve(__dirname, "node_modules/d3-scale"),
            "d3-axis$": path.resolve(__dirname, "node_modules/d3-axis")
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};

const path_to_badge = path.resolve(
    __dirname,
    "../../../src/www/scripts/project/admin/permissions-per-group/"
);

const webpack_config_for_vue = {
    entry: {
        "admin-nature": "./admin-nature.js",
        "global-admin": "./global-admin.js",
        "tracker-report-expert-mode": "./report/index.js",
        "tracker-permissions-per-group": "./permissions-per-group/src/index.js",
        "tracker-workflow-transitions": "./workflow-transitions/src/index.js",
        MoveArtifactModal: "./artifact-action-buttons/src/index.js",
        TrackerAdminFields: "./TrackerAdminFields.js",
        "tracker-semantic-timeframe-option-selector": "./semantic-timeframe-option-selector"
    },
    context,
    output,
    externals: {
        codendi: "codendi",
        jquery: "jQuery",
        tlp: "tlp"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(webpack_configurator.tlp_fetch_alias, {
            "permission-badge": path_to_badge
        })
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

const webpack_for_vue_plus_typescript = {
    entry: {
        "tracker-creation": "./tracker-creation/index.ts"
    },
    context,
    output,
    resolve: {
        extensions: [".js", ".ts", ".vue"],
        alias: webpack_configurator.extendAliases(webpack_configurator.vue_components_alias)
    },
    externals: {
        tlp: "tlp"
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
            webpack_configurator.rule_file_loader_images
        ]
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(true)
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

const config_for_legacy_scripts = {
    entry: {
        null: "null_entry"
    },
    context,
    output,
    externals: {
        tuleap: "tuleap"
    },
    plugins: [
        ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
            "tracker.js": [
                "./legacy/TrackerReports.js",
                "./legacy/TrackerEmailCopyPaste.js",
                "./legacy/TrackerReportsSaveAsModal.js",
                "./legacy/TrackerBinds.js",
                "./legacy/ReorderColumns.js",
                "./legacy/TrackerTextboxLists.js",
                "./legacy/TrackerAdminFieldWorkflow.js",
                "./legacy/TrackerArtifact.js",
                "./legacy/TrackerArtifactEmailActions.js",
                "./legacy/TrackerArtifactLink.js",
                "./legacy/LoadTrackerArtifactLink.js",
                "./legacy/TrackerCreate.js",
                "./legacy/TrackerFormElementFieldPermissions.js",
                "./legacy/TrackerDateReminderForms.js",
                "./legacy/TrackerTriggers.js",
                "./legacy/SubmissionKeeper.js",
                "./legacy/TrackerFieldDependencies.js",
                "./legacy/TrackerRichTextEditor.js",
                "./legacy/artifactChildren.js",
                "./legacy/load-artifactChildren.js",
                "./legacy/modal-in-place.js",
                "./legacy/TrackerArtifactEditionSwitcher.js",
                "./legacy/FixAggregatesHeaderHeight.js",
                "./legacy/TrackerSettings.js",
                "./legacy/TrackerCollapseFieldset.js",
                "./legacy/CopyArtifact.js",
                "./legacy/tracker-report-nature-column.js",
                "./legacy/tracker-admin-notifications.js",
                "./legacy/tracker-admin-notifications-popover.js",
                "./legacy/tracker-webhooks.js"
            ]
        }),
        manifest_plugin
    ]
};

module.exports = [
    webpack_config_for_burndown_chart,
    webpack_config_for_vue,
    webpack_for_vue_plus_typescript,
    config_for_legacy_scripts
];
