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

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets/"),
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
            // deduplicate moment that is also used by chart-builder
            moment$: path.resolve(__dirname, "node_modules/moment"),
        },
    },
    module: {
        rules: [webpack_configurator.rule_po_files],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const config_for_flaming_parrot = {
    entry: {
        "children-view": "./scripts/artifact/artifact-children.ts",
        "create-view": "./scripts/artifact/create-view.ts",
        "cross-references-fields": "./scripts/form-element/src/cross-references-fields.ts",
        "edit-view": "./scripts/artifact/edition/edit-view.ts",
        "list-fields": "./scripts/artifact/list-fields.ts",
        "run-field-dependencies": "./scripts/artifact/run-field-dependencies.ts",
        "artifact-links-field": "./scripts/artifact/edition/artifact-links-field.ts",
        "mass-change": "./scripts/artifact/mass-change/mass-change-view.ts",
        "modal-v2": "./scripts/modal-v2/modal-in-place.js",
        "tracker-admin": "./scripts/tracker-admin/index.js",
        "tracker-creation-success": "./scripts/tracker-creation-success-modal/index.ts",
        "tracker-email-copy-paste-fp": "./scripts/artifact/tracker-email-copy-paste-fp.ts",
        "tracker-report-expert-mode": "./scripts/report/index.js",
        "tracker-semantic-progress-options-selector":
            "./scripts/semantics/progress/admin-selectors.ts",
        "tracker-admin-fields-permissions": "./scripts/tracker-admin/admin-fields-permissions",
        "tracker-semantic-status": "./scripts/semantics/status/status-picker.ts",
        "tracker-semantic-done": "./scripts/semantics/status/done-picker.ts",
        TrackerAdminFields: "./scripts/TrackerAdminFields.js",
    },
    context,
    output,
    externals: {
        ckeditor4: "CKEDITOR",
        codendi: "codendi",
        jquery: "jQuery",
    },
    resolve: {
        extensions: [".js", ".ts"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getTypescriptCheckerPlugin(false)],
};

const config_for_burning_parrot = {
    entry: {
        "admin-type": "./scripts/admin-type.js",
        "global-admin-artifact-links": "./scripts/global-admin/artifact-links.js",
        "global-admin-trackers": "./scripts/global-admin/trackers.ts",
        "tracker-email-copy-paste-bp": "./scripts/artifact/tracker-email-copy-paste-bp.ts",
        "tracker-homepage": "./scripts/tracker-homepage/src/index.ts",
    },
    context,
    output,
    externals: {
        jquery: "jQuery",
        tlp: "tlp",
    },
    resolve: {
        extensions: [".js", ".ts"],
    },
    module: {
        rules: [...webpack_configurator.configureTypescriptRules()],
    },
    plugins: [manifest_plugin, webpack_configurator.getTypescriptCheckerPlugin(false)],
};

let entry_points = {
    "style-fp": "./themes/FlamingParrot/css/style.scss",
    print: "./themes/default/css/print.scss",
    "burndown-chart": "./themes/burndown-chart.scss",
    colorpicker: "./themes/FlamingParrot/css/colorpicker.scss",
    "dependencies-matrix": "./themes/FlamingParrot/css/dependencies-matrix.scss",
    "tracker-bp": "./themes/BurningParrot/css/tracker.scss",
};

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
    config_for_flaming_parrot,
    config_for_burning_parrot,
    config_for_themes,
];
