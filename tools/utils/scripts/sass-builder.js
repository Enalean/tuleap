/*
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

var gulp = require("gulp");
var sass = require("gulp-sass");
var rev = require("gulp-rev");
var path = require("path");
var del = require("del");
var merge = require("merge2");
var pump = require("pump");

function cleanSass(base_dir, scss_files) {
    var css_files = scss_files.map(function(file) {
        var filename = path.basename(file, path.extname(file)) + ".css",
            css_path = path.join(base_dir, path.dirname(file), filename);
        return css_path;
    });
    return del(css_files);
}

function buildSass(base_dir, scss_hash, callback) {
    var sass_options = { outputStyle: "compressed" };

    if ("includes" in scss_hash) {
        sass_options.includePaths = scss_hash.includes.map(function(p) {
            return path.join(base_dir, p);
        });
    }

    var target_path = path.join(base_dir, scss_hash.target_dir);

    if (!scss_hash.is_revisioned) {
        return pump(
            gulp.src(scss_hash.files, { cwd: base_dir }),
            sass(sass_options),
            gulp.dest(target_path),
            function(err) {
                if (err) {
                    callback(err);
                }
            }
        );
    }

    return pump(
        gulp.src(scss_hash.files, { cwd: base_dir }),
        sass(sass_options),
        rev(),
        gulp.dest(target_path),
        rev.manifest(path.join(target_path, "manifest.json"), {
            base: target_path,
            merge: true
        }),
        gulp.dest(target_path),
        function(err) {
            if (err) {
                callback(err);
            }
        }
    );
}

function cleanAndBuildSass(sass_task_name, base_dir, scss_hash, dependent_tasks) {
    var all_theme_names = Object.keys(scss_hash.themes);
    var clean_task_name = "clean-" + sass_task_name;

    gulp.task(clean_task_name, function() {
        var promises = all_theme_names.map(function(theme_name) {
            var theme = scss_hash.themes[theme_name];

            if (!theme.is_revisioned) {
                return cleanSass(base_dir, theme.files);
            }

            return del(path.resolve(base_dir, theme.target_dir));
        });

        return Promise.all(promises);
    });

    var dependent_tasks_array = dependent_tasks || [];
    var dependencies = [clean_task_name].concat(dependent_tasks_array);
    gulp.task(sass_task_name, dependencies, function(callback) {
        var streams = all_theme_names.map(function(theme) {
            return buildSass(base_dir, scss_hash.themes[theme], callback);
        });

        return merge(streams);
    });
}

module.exports = {
    cleanAndBuildSass: cleanAndBuildSass
};
