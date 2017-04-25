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

var pkg         = require('./package.json');
var gulp        = require('gulp');
var sass        = require('gulp-sass');
var concat      = require('gulp-concat');
var uglify      = require('gulp-uglify');
var scsslint    = require('gulp-scss-lint');
var rename      = require('gulp-rename');
var header      = require('gulp-header');
var streamqueue = require('streamqueue');
var babel       = require('gulp-babel');
var runSequence = require('run-sequence');

var locales = ['en_US', 'fr_FR'];
var colors  = ['orange', 'blue', 'green', 'red', 'grey', 'purple'];
var banner  = [
    '/**',
    ' * <%= pkg.name %> v<%= pkg.version %>',
    ' *',
    ' * Copyright (c) <%= pkg.author %>, 2017. All Rights Reserved.',
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


gulp.task('default', ['build']);
gulp.task('build', ['assets', 'js', 'sass:prod', 'sass:doc']);
gulp.task('watch', ['sass:watch', 'js:watch']);

/************************************************
 * SASS
 ***********************************************/
gulp.task('sass:prod', ['sass:compress']);

gulp.task('sass:watch', ['sass:compress'], function() {
    gulp.watch('./doc/css/**/*.scss', ['sass:doc']);
    gulp.watch('./src/scss/**/*.scss', ['sass:dev']);
});

gulp.task('sass:lint', function() {
    return gulp.src('./src/scss/**/*.scss')
        .pipe(scsslint({
            config: '.scss-lint.yml'
        })).pipe(scsslint.failReporter('E'));
});

var color_tasks = declareSassCompressTasks();

gulp.task('sass:compress', function(cb) {
    return runSequence(color_tasks, cb);
});

gulp.task('sass:dev', ['sass:lint'], function(cb) {
    return runSequence('sass:compress', cb);
});

gulp.task('sass:doc', function() {
    return gulp.src('./doc/css/*.scss')
        .pipe(scsslint({
            config: '.scss-lint.yml'
        }))
        .pipe(
            sass({
                outputStyle: 'compressed'
            })
            .on('error', sass.logError)
        )
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(header(banner, { pkg: pkg }))
        .pipe(gulp.dest('./doc/css'));
});

function declareSassCompressTasks() {
    return colors.map(function (color) {
        var color_task_name = 'sass:compress-' + color;
        gulp.task(color_task_name, function() {
            return compressForAGivenColor(color);
        });

        return color_task_name;
    });
}

function compressForAGivenColor(color) {
    return gulp.src('./src/scss/tlp-' + color + '.scss')
        .pipe(
            sass({
                outputStyle: 'compressed'
            })
            .on('error', sass.logError)
        )
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

gulp.task('js:watch', function() {
    gulp.watch('./src/js/**/*.js', ['js']);
});

gulp.task('js:compile', locales.map(function (locale) { return 'js:compile-' + locale; }));

locales.forEach(function (locale) {
    gulp.task('js:compile-' + locale, function() {
        compileForAGivenLocale(locale);
    });
});

function compileForAGivenLocale(locale) {
    var tlp_files    = gulp.src('./src/js/**/*.js')
        .pipe(babel({ presets: ['es2015'] }))
        .pipe(uglify())
        .pipe(header(banner, { pkg: pkg })),
        vendor_files = gulp.src('./src/vendor/**/*.js'),
        overrides    = gulp.src('./src/vendor-overrides/**/*.js').pipe(uglify()).pipe(header(banner, { pkg: pkg })),
        locale_files = gulp.src('./src/vendor-i18n/' + locale + '/**/*.js').pipe(uglify());

    return streamqueue({ objectMode: true }, tlp_files, vendor_files, overrides, locale_files)
        .pipe(concat('tlp.' + locale + '.min.js'))
        .pipe(gulp.dest('./dist'));
}

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
