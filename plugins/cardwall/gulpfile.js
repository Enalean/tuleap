'use strict';

var gulp    = require('gulp'),
    concat  = require('gulp-concat'),
    rev     = require('gulp-rev'),
    del     = require('del'),
    jsFiles = [
        'www/js/ajaxInPlaceEditorExtensions.js',
        'www/js/cardwall.js',
        'www/js/script.js',
        'www/js/custom-mapping.js',
        'www/js/CardsEditInPlace.js',
        'www/js/fullscreen.js'
    ],
    asset_dir = 'www/assets/';

gulp.task('clean', function() {
    del(asset_dir);
});

gulp.task('concat', ['clean'], function() {
    return gulp.src(jsFiles)
        .pipe(concat('cardwall.js'))
        .pipe(rev())
        .pipe(gulp.dest(asset_dir))
        .pipe(rev.manifest('manifest.json'))
        .pipe(gulp.dest(asset_dir));
});

gulp.task('watch',function() {
    gulp.watch(jsFiles, ['concat']);
});

gulp.task('build',  ['concat']);
gulp.task('default', ['build']);
