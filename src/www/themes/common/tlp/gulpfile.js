/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

var pkg = require("./package.json");
var gulp = require("gulp");
var sass = require("gulp-sass");
var scsslint = require("gulp-scss-lint");
var rename = require("gulp-rename");
var header = require("gulp-header");
var runSequence = require("run-sequence");
var path = require("path");
var del = require("del");

var colors = ["orange", "blue", "green", "red", "grey", "purple"];
var banner = [
    "/**",
    " * <%= pkg.name %> v<%= pkg.version %>",
    " *",
    " * Copyright (c) <%= pkg.author %>, 2017. All Rights Reserved.",
    " *",
    " * This file is a part of <%= pkg.name %>.",
    " *",
    " * Tuleap is free software; you can redistribute it and/or modify",
    " * it under the terms of the GNU General Public License as published by",
    " * the Free Software Foundation; either version 2 of the License, or",
    " * (at your option) any later version.",
    " *",
    " * Tuleap is distributed in the hope that it will be useful,",
    " * but WITHOUT ANY WARRANTY; without even the implied warranty of",
    " * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the",
    " * GNU General Public License for more details.",
    " *",
    " * You should have received a copy of the GNU General Public License",
    " * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.",
    " */",
    ""
].join("\n");

var target_dir = path.resolve(__dirname, "./dist");

gulp.task("default", ["build"]);

gulp.task("clean-dist", function() {
    return del(target_dir);
});

gulp.task("build", ["clean-dist"], function(cb) {
    return runSequence(["assets", "sass:prod", "sass:doc"], cb);
});
gulp.task("watch", ["assets", "sass:watch"]);

/************************************************
 * SASS
 ***********************************************/
gulp.task("sass:prod", ["sass:compress"]);

gulp.task("sass:watch", ["sass:compress", "sass:doc"], function() {
    gulp.watch("./doc/css/**/*.scss", ["sass:dev-doc"]);
    gulp.watch("./src/scss/**/*.scss", ["sass:dev"]);
});

gulp.task("sass:lint", function() {
    return gulp
        .src("./src/scss/**/*.scss")
        .pipe(
            scsslint({
                config: ".scss-lint.yml"
            })
        )
        .pipe(scsslint.failReporter("E"));
});

var color_tasks = declareSassCompressTasks();

gulp.task("sass:compress", function(cb) {
    return runSequence(color_tasks, cb);
});

gulp.task("sass:dev", ["sass:lint"], function(cb) {
    return runSequence("sass:compress", cb);
});

gulp.task("sass:lint-doc", function() {
    return gulp
        .src("./doc/css/*.scss")
        .pipe(
            scsslint({
                config: ".scss-lint.yml"
            })
        )
        .pipe(scsslint.failReporter("E"));
});

gulp.task("sass:dev-doc", ["sass:lint-doc"], function(cb) {
    return runSequence("sass:doc", cb);
});

gulp.task("sass:doc", function() {
    return gulp
        .src("./doc/css/*.scss")
        .pipe(
            sass({
                outputStyle: "compressed"
            })
        )
        .pipe(
            rename({
                suffix: ".min"
            })
        )
        .pipe(header(banner, { pkg: pkg }))
        .pipe(gulp.dest("./doc/css"));
});

function declareSassCompressTasks() {
    return colors.map(function(color) {
        var color_task_name = "sass:compress-" + color;
        gulp.task(color_task_name, function() {
            return compressForAGivenColor(color);
        });

        return color_task_name;
    });
}

function compressForAGivenColor(color) {
    return gulp
        .src("./src/scss/tlp-" + color + "*.scss")
        .pipe(
            sass({
                outputStyle: "compressed"
            })
        )
        .pipe(
            rename({
                suffix: ".min"
            })
        )
        .pipe(header(banner, { pkg: pkg }))
        .pipe(gulp.dest(target_dir));
}

/************************************************
 * Assets
 ***********************************************/
gulp.task("assets", ["assets:fonts", "assets:images"]);

gulp.task("assets:fonts", function() {
    return gulp.src("./src/fonts/**/*").pipe(gulp.dest(target_dir + "/fonts"));
});

gulp.task("assets:images", function() {
    return gulp.src("./src/images/**/*").pipe(gulp.dest(target_dir + "/images"));
});
