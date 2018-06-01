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

const component_builder = require("./component-builder.js");
const sass_builder = require("./sass-builder.js");

function getAllPluginsFromManifestFiles() {
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

function concatCoreJs(files_hash, target_dir) {
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

function getCleanJsTask(plugin_name, plugin_assets_path) {
    const cleanTask = () => del(plugin_assets_path);
    cleanTask.displayName = "clean-js-" + plugin_name;
    return cleanTask;
}

function orderManifestsByDependencies() {
    const manifest_files = getAllPluginsFromManifestFiles();
    const map_of_manifest_files = new Map([]);
    manifest_files.forEach(manifest => {
        map_of_manifest_files.set(manifest.name, manifest);
    });
    const ordered_manifests = manifest_files.reduceRight((accumulator, manifest) => {
        if ("dependencies" in manifest) {
            const dependencies_manifests = manifest.dependencies.map(dependency_name =>
                map_of_manifest_files.get(dependency_name)
            );
            return [...dependencies_manifests, manifest].concat(accumulator);
        }
        return [manifest].concat(accumulator);
    }, []);
    const unique_manifests = [...new Set(ordered_manifests)];
    return unique_manifests;
}

function getPluginTasks(asset_dir) {
    const sass_tasks = [];
    const all_plugins_tasks = orderManifestsByDependencies().map(manifest => {
        const name = manifest.name;
        const base_dir = path.join("plugins", name);

        let plugin_tasks = [];
        const cleanJsTask = getCleanJsTask(name, path.join(base_dir, asset_dir));

        if ("components" in manifest) {
            const pluginComponentTasks = component_builder.getComponentsBuildTasks(
                base_dir,
                manifest.components
            );
            pluginComponentTasks.displayName = "components-" + name;
            plugin_tasks.push(gulp.series(cleanJsTask, pluginComponentTasks));
        }

        if ("javascript" in manifest) {
            // If there are components, they already cleaned and added new stuff
            // in assets folder. We should not clean it twice.
            const jsTasks = [];
            if (!("components" in manifest)) {
                jsTasks.push(cleanJsTask);
            }

            const buildPluginJavascriptTask = () =>
                concatPluginJavascript(base_dir, name + ".js", manifest.javascript, asset_dir);
            buildPluginJavascriptTask.displayName = "js-" + name;
            jsTasks.push(buildPluginJavascriptTask);
            const cleanAndBuildJsTask = gulp.series(...jsTasks);

            plugin_tasks.push(cleanAndBuildJsTask);
        }

        if ("themes" in manifest) {
            const sass_task_name = "sass-" + name;
            const pluginSassTasks = sass_builder.getSassTasks(sass_task_name, base_dir, manifest);

            plugin_tasks.push(pluginSassTasks);
            sass_tasks.push(pluginSassTasks);
        }

        if (plugin_tasks.length === 0) {
            throw new Error(
                "build-manifest.json file at " + base_dir + " resulted in no task. Please delete it"
            );
        }
        const pluginTask = gulp.series(...plugin_tasks);
        pluginTask.displayName = "build-plugin-" + name;
        return pluginTask;
    });

    return {
        all_plugins_tasks: gulp.series(...all_plugins_tasks),
        sass_tasks: gulp.series(...sass_tasks)
    };
}

function concatPluginJavascript(base_dir, name, files, asset_dir) {
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

function getPluginsWatchTasks(asset_dir) {
    getAllPluginsFromManifestFiles().forEach(manifest => {
        const name = manifest.name,
            base_dir = path.join("plugins", name);

        if ("javascript" in manifest) {
            const buildPluginJavascriptTask = () =>
                concatPluginJavascript(base_dir, name + ".js", manifest.javascript, asset_dir);
            gulp.watch(
                manifest.javascript.map(filepath => path.join(base_dir, filepath)),
                buildPluginJavascriptTask
            );
        }

        if ("themes" in manifest) {
            const sass_task_name = "sass-" + name;
            const pluginSassTasks = sass_builder.getSassTasks(sass_task_name, base_dir, manifest);

            let files = [];
            Object.keys(manifest.themes).forEach(theme_name => {
                files = files.concat(
                    manifest.themes[theme_name].files.map(filepath => path.join(base_dir, filepath))
                );
                const watched_includes = manifest.themes[theme_name].watched_includes;
                if (watched_includes) {
                    files = files.concat(
                        watched_includes.map(filepath => path.join(base_dir, filepath))
                    );
                }
            });

            gulp.watch(files, pluginSassTasks);
        }
    });
}

module.exports = {
    concatCoreJs,
    getPluginTasks,
    getPluginsWatchTasks
};
