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

const { series } = require("gulp");
const readPkg = require("read-pkg");
const path = require("path");
const { spawn } = require("child_process");

function verifyPackageJsonFile(component_path) {
    const package_json_path = path.join(component_path, "package.json");

    const package_json = readPkg.sync({ cwd: component_path });
    if (!package_json.name) {
        throw new Error("package.json file should have a 'name' " + package_json_path);
    }

    if (!package_json.scripts || !package_json.scripts.build) {
        throw new Error("package.json file should have a 'build' script " + package_json_path);
    }

    return {
        name: package_json.name,
        path: component_path
    };
}

function getNpmBuildTask(component) {
    const task = () =>
        spawn("npm", ["run", "build"], {
            stdio: "inherit",
            cwd: component.path
        });
    task.displayName = "build-" + component.name;
    return task;
}

function readPackageJsonAndBuildTasks(component_path) {
    const component = verifyPackageJsonFile(component_path);
    const task = series(getNpmBuildTask(component));
    task.displayName = "component";
    return task;
}

function buildComponentTasks(base_dir, component_partial_path) {
    const full_path = path.join(base_dir, component_partial_path);
    return readPackageJsonAndBuildTasks(full_path);
}

function getComponentsBuildTasks(base_dir, component_paths) {
    const tasks = component_paths.map(p => buildComponentTasks(base_dir, p));
    return series(...tasks);
}

module.exports = {
    getComponentsBuildTasks
};
