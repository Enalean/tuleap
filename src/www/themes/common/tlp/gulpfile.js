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
var rev         = require('gulp-rev');
var path        = require('path');
var del         = require('del');
var clone       = require('gulp-clone');

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

var vendor_dependencies_in_order = [
    './src/vendor/flatpickr-1.9.1/flatpickr.min.js',
    './src/vendor/jquery-2.1.0/jquery-2.1.0.min.js',
    './src/vendor/select2-4.0.3/select2.full.min.js',
];

var target_dir = path.resolve(__dirname, './dist');

gulp.task('default', ['build']);

gulp.task('clean-dist', function() {
    return del(target_dir);
})

gulp.task('build', ['clean-dist'], function(cb) {
    return runSequence([
        'assets',
        'js',
        'sass:prod',
        'sass:doc'
    ], cb)
});
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
        .pipe(gulp.dest(target_dir));
}

/************************************************
 * Javascript
 ***********************************************/
gulp.task('js', ['js:compile']);

gulp.task('js:watch', function() {
    gulp.watch('./src/js/**/*.js', ['js']);
});

gulp.task('js:compile', function() {
    var tlp_files    = gulp.src('./src/js/**/*.js')
        .pipe(babel({ presets: ['es2015'] }))
        .pipe(uglify())
        .pipe(header(banner, { pkg: pkg }));
    var vendor_files = gulp.src(vendor_dependencies_in_order);
    var overrides    = gulp.src('./src/vendor-overrides/**/*.js')
        .pipe(uglify())
        .pipe(header(banner, { pkg: pkg }));

    var common_streams = [tlp_files, vendor_files, overrides];

    var localized_files_streams = locales.map(function (locale) {
        var cloned_common_streams = common_streams.map(function(stream) {
            return stream.pipe(clone());
        });
        var localized_vendor_stream = gulp.src('./src/vendor-i18n/' + locale + '/**/*.js')
            .pipe(uglify());

        cloned_common_streams.push(localized_vendor_stream);

        var all_files_stream = mergeStreams(cloned_common_streams);

        return all_files_stream.pipe(concat('tlp.' + locale + '.min.js'))
    });

    return hashAllFilesAndGenerateManifest(mergeStreams(localized_files_streams));
});

function mergeStreams(streams_array) {
    // When we use node > 4.x, we'll use the spread operator
    var merged_stream = new streamqueue({ objectMode: true });
        streams_array.forEach(function(stream) {
            merged_stream.queue(stream);
        });
        merged_stream.done();

        return merged_stream;
}

function hashAllFilesAndGenerateManifest(stream) {
    stream.pipe(rev())
        .pipe(gulp.dest(target_dir))
        .pipe(rev.manifest({
            path : target_dir + '/manifest.json',
            base : target_dir,
            merge: true
        }))
        .pipe(gulp.dest(target_dir));

    return stream;
}

/************************************************
 * Assets
 ***********************************************/
gulp.task('assets', ['assets:fonts', 'assets:images']);

gulp.task('assets:fonts', function() {
    return gulp.src('./src/fonts/**/*')
        .pipe(gulp.dest(target_dir + '/fonts'));
});

gulp.task('assets:images', function() {
    return gulp.src('./src/images/**/*')
        .pipe(gulp.dest(target_dir + '/images'));
});
