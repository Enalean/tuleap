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
const sass = require("gulp-sass");
const rev = require("gulp-rev");
const path = require("path");
const del = require("del");
const pump = require("pump");

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

function buildCleanTask(base_dir, theme) {
    return () => {
        if (!theme.is_revisioned) {
            return cleanSass(base_dir, theme.files);
        }

        return del(path.resolve(base_dir, theme.target_dir));
    };
}

function getSassTasks(sass_task_name, base_dir, scss_hash) {
    const all_theme_names = Object.keys(scss_hash.themes);

    const tasks = all_theme_names.map(theme_name => {
        const theme = scss_hash.themes[theme_name];
        const cleanTask = buildCleanTask(base_dir, theme);
        cleanTask.displayName = `clean-${sass_task_name}-${theme_name}`;

        const buildSassTask = callback => buildSass(base_dir, theme, callback);
        buildSassTask.displayName = `${sass_task_name}-${theme_name}`;
        return gulp.series(cleanTask, buildSassTask);
    });

    return gulp.series(...tasks);
}

module.exports = {
    getSassTasks
};
