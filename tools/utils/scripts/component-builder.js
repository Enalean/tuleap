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

const gulp = require("gulp");
const runSequence = require("run-sequence");
const readPkg = require("read-pkg");
const path = require("path");
const exec = require("child_process").exec;
const spawn = require("child_process").spawn;

function verifyPackageJsonFile(component_path) {
    const package_json_path = path.join(component_path, "package.json");

    return readPkg({ cwd: component_path })
        .then(package_json => {
            if (!package_json.name) {
                throw new Error("package.json file should have a 'name' " + package_json_path);
            }

            if (!package_json.scripts || !package_json.scripts.build) {
                throw new Error(
                    "package.json file should have a 'build' script " + package_json_path
                );
            }

            return {
                name: package_json.name,
                path: component_path
            };
        })
        .catch(() => {
            throw new Error("package.json file could not be found at " + package_json_path);
        });
}

function findComponentsWithPackageAndBuildScript(component_paths) {
    const promises = component_paths.map(path => verifyPackageJsonFile(path));

    return Promise.all(promises);
}

function installNpmComponent(component) {
    var task_name = "install-" + component.name;
    gulp.task(task_name, function(callback) {
        exec(
            "npm install",
            {
                cwd: component.path
            },
            function(error) {
                if (error) {
                    return callback(error);
                }
                callback();
            }
        );
    });

    return task_name;
}

function buildNpmComponent(component, dependent_tasks) {
    var task_name = "build-" + component.name;
    gulp.task(task_name, dependent_tasks, function(callback) {
        var child_process = spawn("npm", ["run", "build"], {
            stdio: "inherit",
            cwd: component.path
        });

        child_process.on("close", function(code) {
            if (code !== 0) {
                return callback(code);
            }
            callback();
        });
    });

    return task_name;
}

function installAndBuildNpmComponents(
    base_dir,
    component_paths,
    components_task_name,
    dependent_tasks
) {
    const build_tasks = [];
    const full_component_paths = component_paths.map(p => path.join(base_dir, p));

    var promise = findComponentsWithPackageAndBuildScript(full_component_paths).then(function(
        components
    ) {
        components.forEach(function(component) {
            var install_task_name = installNpmComponent(component);
            var build_task_name = buildNpmComponent(component, [install_task_name]);
            build_tasks.push(build_task_name);
        });
    });

    gulp.task(components_task_name, dependent_tasks, function(callback) {
        promise
            .then(() => {
                return runSequence(...build_tasks, callback);
            })
            .catch(error => callback(error));
    });
}

module.exports = {
    installAndBuildNpmComponents
};
