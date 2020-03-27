/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
const gettext = require("gulp-angular-gettext");
const path = require("path");

const templates_with_translated_strings_glob = "src/**/*.tpl.html";
const javascript_with_translated_strings_glob = "src/**/*.js";

function extractGettext() {
    return gulp
        .src([templates_with_translated_strings_glob, javascript_with_translated_strings_glob], {
            cwd: path.resolve("./scripts/angular-artifact-modal"),
            cwdbase: true,
        })
        .pipe(
            gettext.extract("template.pot", {
                lineNumbers: false,
            })
        )
        .pipe(gulp.dest("scripts/angular-artifact-modal/po/"));
}

function watchTask() {
    gulp.watch(
        [templates_with_translated_strings_glob, javascript_with_translated_strings_glob],
        { cwd: path.resolve("./scripts/angular-artifact-modal") },
        extractGettext
    );
}

module.exports = {
    watch: watchTask,
};
