'use strict';

var gulp = require('gulp');
var path = require('path');
var del  = require('del');
var sass = require('gulp-sass');

var default_theme_scss = {
    files: [
        'www/themes/default/css/style.scss',
    ],
    target_dir: 'www/themes/default/css'
};

var theme_flamingparrot_scss = {
    files: [
        'www/themes/FlamingParrot/css/style.scss'
    ],
    target_dir: 'www/themes/FlamingParrot/css'
};

function sass_build(base_dir, scss_hash) {
    var sass_options = {
        outputStyle : 'compressed',
        includePaths: '/tuleap/plugins/trafficlights/www/themes/FlamingParrot/css'
    };

    return gulp.src(scss_hash.files, { cwd: base_dir })
        .pipe(sass(sass_options).on('error', sass.logError))
        .pipe(gulp.dest(path.join(base_dir, scss_hash.target_dir)));
}

function sass_clean(base_dir, scss_files) {
    var css_files = scss_files.map(function(file) {
        var filename = path.basename(file, path.extname(file)) + '.css',
            css_path = path.join(base_dir, path.dirname(file), filename);
        return css_path;
    });
    del(css_files);
}

gulp.task('clean-sass', function() {
    sass_clean('.', default_theme_scss.files);
    sass_clean('.', theme_flamingparrot_scss.files);
});

gulp.task('sass', ['clean-sass'], function() {
    sass_build('.', default_theme_scss);
    sass_build('.', theme_flamingparrot_scss);
});

gulp.task('build', ['sass']);

gulp.task('default', ['build']);
