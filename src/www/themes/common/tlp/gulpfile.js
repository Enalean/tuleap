/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

var pkg        = require('./package.json');
var gulp       = require('gulp');
var sass       = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var concat     = require('gulp-concat');
var uglify     = require('gulp-uglify');
var scsslint   = require('gulp-scss-lint');
var rename     = require('gulp-rename');
var header     = require('gulp-header');
var replace    = require('gulp-replace');

var colors = ['orange', 'blue', 'green', 'red', 'grey', 'purple'];
var banner = [
   '/**',
   ' * <%= pkg.name %> v<%= pkg.version %>',
   ' *',
   ' * Copyright (c) <%= pkg.author %>, 2016. All Rights Reserved.',
   ' *',
   ' * This file is a part of <%= pkg.name %>.',
   ' *',
   ' * Tuleap is free software; you can redistribute it and/or modify',
   ' * it under the terms of the GNU General Public License as published by',
   ' * the Free Software Foundation; either version 2 of the License, or',
   ' * (at your option) any later version.',
   ' *',
   ' * Tuleap is distributed in the hope that it will be useful,',
   ' * but WITHOUT ANY WARRANTY; without even the implied warranty of',
   ' * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the',
   ' * GNU General Public License for more details.',
   ' *',
   ' * You should have received a copy of the GNU General Public License',
   ' * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.',
   ' */',
   ''
].join('\n');

gulp.task('default', ['assets', 'js', 'sass', 'sass:doc']);
gulp.task('watch', ['sass:watch', 'js:watch']);

/************************************************
 * SASS
 ***********************************************/
gulp.task('sass', ['sass:lint', 'sass:compress']);

gulp.task('sass:watch', function () {
    gulp.watch('./src/scss/**/*.scss', ['sass']);
});

gulp.task('sass:lint', function () {
    return gulp.src('./src/scss/*.scss')
        .pipe(scsslint({
            'config': '.scss-lint.yml'
        }))
});

gulp.task('sass:compress', colors.map(function (color) { return 'sass:compress-' + color; }));

colors.forEach(function (color) {
    gulp.task('sass:compress-' + color, function () {
        return compressForAGivenColor(color);
    });
});

gulp.task('sass:doc', function () {
    return gulp.src('./doc/css/*.scss')
        .pipe(
            sass({
                outputStyle: 'compressed'
            })
        .on('error', sass.logError))
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(header(banner, { pkg: pkg }))
        .pipe(gulp.dest('./doc/css'));
});

function compressForAGivenColor(color) {
    return gulp.src('./src/scss/tlp-' + color + '.scss')
        .pipe(
            sass({
                outputStyle: 'compressed'
            })
        .on('error', sass.logError))
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(header(banner, { pkg: pkg }))
        .pipe(gulp.dest('./dist'));
}

/************************************************
 * Javascript
 ***********************************************/
gulp.task('js', ['js:compile']);

gulp.task('js:watch', function () {
    gulp.watch('./src/js/*.js', ['js']);
});

gulp.task('js:compile', function() {
    return gulp.src('./src/js/*.js')
        .pipe(concat('tlp.js'))
        .pipe(uglify())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(header(banner, { pkg: pkg }))
        .pipe(gulp.dest('./dist'));
});

/************************************************
 * Assets
 ***********************************************/
gulp.task('assets', ['assets:fonts', 'assets:images']);

gulp.task('assets:fonts', function() {
    return gulp.src('./src/fonts/**/*')
        .pipe(gulp.dest('./dist/fonts'));
});

gulp.task('assets:images', function() {
    return gulp.src('./src/images/**/*')
        .pipe(gulp.dest('./dist/images'));
});
