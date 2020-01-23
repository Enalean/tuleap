/*
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

const { series } = require("gulp");
const del = require("del");

const tuleap = require("./tools/utils/scripts/tuleap-gulp-build");
const component_builder = require("./tools/utils/scripts/component-builder.js");

const core_build_manifest = require("./build-manifest.json");
const asset_dir = "www/assets";
const base_dir = ".";

function cleanCoreJs() {
    return del("src/" + asset_dir + "/*");
}

const { all_plugins_tasks } = tuleap.getPluginTasks(asset_dir);

const pluginsTask = all_plugins_tasks;

const buildCoreComponents = component_builder.getComponentsBuildTasks(
    base_dir,
    core_build_manifest.components
);

function watchTask() {
    tuleap.getPluginsWatchTasks(asset_dir);
}

const buildTask = series(cleanCoreJs, buildCoreComponents, pluginsTask);

module.exports = {
    default: buildTask,
    build: buildTask,
    watch: watchTask
};
