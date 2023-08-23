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
    path.resolve(__dirname, "./frontend-assets/"),
    "/assets/trackers/legacy/"
);

const config_for_legacy_scripts = {
    entry: {},
    context,
    output,
    externals: {
        tuleap: "tuleap",
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
            "tracker.js": [
                "./src/TrackerReports.js",
                "./src/TrackerReportsSaveAsModal.js",
                "./src/TrackerBinds.js",
                "./src/ReorderColumns.js",
                "./src/TrackerTextboxLists.js",
                "./src/TrackerAdminFieldWorkflow.js",
                "./src/TrackerArtifact.js",
                "./src/TrackerArtifactEmailActions.js",
                "./src/TrackerArtifactLink.js",
                "./src/LoadTrackerArtifactLink.js",
                "./src/TrackerCreate.js",
                "./src/TrackerFormElementFieldPermissions.js",
                "./src/TrackerDateReminderForms.js",
                "./src/TrackerTriggers.js",
                "./src/SubmissionKeeper.js",
                "./src/TrackerFieldDependencies.js",
                "./src/artifactChildren.js",
                "./src/FixAggregatesHeaderHeight.js",
                "./src/TrackerSettings.js",
                "./src/TrackerCollapseFieldset.js",
                "./src/CopyArtifact.js",
                "./src/tracker-report-type-column.js",
                "./src/tracker-webhooks.js",
            ],
        }),
        webpack_configurator.getManifestPlugin(),
    ],
};

module.exports = [config_for_legacy_scripts];
