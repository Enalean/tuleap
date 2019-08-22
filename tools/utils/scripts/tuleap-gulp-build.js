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

const gulp = require("gulp");
const merge = require("merge2");
// eslint-disable-next-line you-dont-need-lodash-underscore/map
const map = require("lodash.map");
const concat = require("gulp-concat");
const rev = require("gulp-rev");
const del = require("del");
const fs = require("fs");
const path = require("path");
const runSequence = require("run-sequence");

const component_builder = require("./component-builder.js");
const sass_builder = require("./sass-builder.js");

runSequence.options.ignoreUndefinedTasks = true;

function get_all_plugins_from_manifests() {
    var plugins_path = "./plugins";

    return fs
        .readdirSync(plugins_path)
        .filter(function(file) {
            try {
                var manifest_path = path.join(plugins_path, file, "build-manifest.json");
                return fs.statSync(manifest_path).isFile();
            } catch (e) {
                return false;
            }
        })
        .map(function(plugin) {
            var manifest_path = path.join(plugins_path, plugin, "build-manifest.json");
            return JSON.parse(fs.readFileSync(manifest_path), "utf8");
        });
}

function concat_all_js_files(files_hash) {
    var streams = map(files_hash, function(files_to_concat, dest_file_name) {
        return gulp.src(files_to_concat).pipe(concat(dest_file_name + ".js"));
    });

    return merge(streams);
}

function concat_core_js(files_hash, target_dir) {
    var merged_stream = concat_all_js_files(files_hash);
    return merged_stream
        .pipe(rev())
        .pipe(gulp.dest(target_dir))
        .pipe(
            rev.manifest(path.join(target_dir, "manifest.json"), {
                base: target_dir,
                merge: true
            })
        )
        .pipe(gulp.dest(target_dir));
}

function declare_plugin_tasks(asset_dir) {
    const javascript_tasks = [];
    const sass_tasks = [];
    const all_plugins_tasks = [];
    const components_tasks = [];

    get_all_plugins_from_manifests().forEach(function(plugin) {
        var name = plugin.name;
        var base_dir = path.join("plugins", name);

        var plugin_tasks = [];
        var plugin_component_task_name;

        if ("dependencies" in plugin) {
            plugin.dependencies.forEach(function(dependency_plugin) {
                const dependency_plugin_task_name = "build-plugin-" + dependency_plugin;
                // Add this plugin's dependencies before it in the task list
                // It is deduplicated afterward
                all_plugins_tasks.push(dependency_plugin_task_name);
            });
        }

        var clean_js_task_name = "clean-js-" + name;
        gulp.task(clean_js_task_name, function() {
            return del(path.join(base_dir, asset_dir));
        });

        if ("components" in plugin) {
            plugin_component_task_name = "components-" + name;

            component_builder.installAndBuildNpmComponents(
                base_dir,
                plugin.components,
                plugin_component_task_name,
                [clean_js_task_name]
            );
            components_tasks.push(plugin_component_task_name);
        }

        if ("javascript" in plugin) {
            var javascript_task_name = "js-" + name;

            // If there are components, they already cleaned and added new stuff
            // in assets folder. We should not clean it twice.
            var js_dependencies = [];
            if (components_tasks.length === 0) {
                js_dependencies.push(clean_js_task_name);
            }

            gulp.task(javascript_task_name, js_dependencies, function() {
                return concat_files_plugin(base_dir, name + ".js", plugin.javascript, asset_dir);
            });

            plugin_tasks.push(javascript_task_name);
            javascript_tasks.push(javascript_task_name);
        }

        if ("themes" in plugin) {
            var sass_task_name = "sass-" + name;
            sass_builder.cleanAndBuildSass(sass_task_name, base_dir, plugin);

            plugin_tasks.push(sass_task_name);
            sass_tasks.push(sass_task_name);
        }

        gulp.task("build-plugin-" + name, function(callback) {
            if (plugin_tasks.length > 0) {
                runSequence(plugin_component_task_name, plugin_tasks, callback);
            } else {
                runSequence(plugin_component_task_name, callback);
            }
        });
        all_plugins_tasks.push("build-plugin-" + name);
    });

    gulp.task("components-plugins", components_tasks);
    gulp.task("js-plugins", javascript_tasks);
    gulp.task("sass-plugins", sass_tasks);
    gulp.task("plugins", callback => {
        const unique_plugins_task = all_plugins_tasks.filter(
            (value, index, array) => array.indexOf(value) === index
        );
        runSequence(...unique_plugins_task, callback);
    });
}

function concat_files_plugin(base_dir, name, files, asset_dir) {
    var assets_paths = path.join(base_dir, asset_dir);

    return gulp
        .src(files, { cwd: base_dir })
        .pipe(concat(name))
        .pipe(rev())
        .pipe(gulp.dest(assets_paths))
        .pipe(
            rev.manifest(path.join(assets_paths, "manifest.json"), {
                base: assets_paths,
                merge: true
            })
        )
        .pipe(gulp.dest(assets_paths));
}

function watch_plugins() {
    get_all_plugins_from_manifests().forEach(function(plugin) {
        var name = plugin["name"],
            base_dir = path.join("plugins", name);

        if ("javascript" in plugin) {
            gulp.watch(
                plugin["javascript"].map(function(file) {
                    return path.join(base_dir, file);
                }),
                ["js-" + name]
            );
        }

        if ("themes" in plugin) {
            var files = [];
            Object.keys(plugin["themes"]).forEach(function(theme) {
                files = files.concat(
                    plugin["themes"][theme]["files"].map(function(file) {
                        return path.join(base_dir, file);
                    })
                );
                var watched_includes = plugin["themes"][theme]["watched_includes"];
                if (watched_includes) {
                    files = files.concat(
                        watched_includes.map(function(file) {
                            return path.join(base_dir, file);
                        })
                    );
                }
            });

            gulp.watch(files, ["sass-" + name]);
        }
    });
}

module.exports = {
    watch_plugins: watch_plugins,
    concat_core_js: concat_core_js,
    declare_plugin_tasks: declare_plugin_tasks
};
