var path        = require('path');
var gulp        = require('gulp');
var gettext     = require('gulp-angular-gettext');
var sass        = require('gulp-sass');
var runSequence = require('run-sequence');

var all_scss_glob                           = 'src/app/**/*.scss';
var main_scss_file                          = 'src/app/planning-v2.scss';
var templates_with_translated_strings_glob  = 'src/app/**/*.tpl.html';
var javascript_with_translated_strings_glob = 'src/app/**/*.js';
var assets_glob                             = 'src/assets/*';
var vendor_assets_files                     = [
    'vendor/artifact-modal/dist/assets/artifact_attachment_default.png',
    'vendor/artifact-modal/dist/assets/loader-mini.gif'
];
var build_dir = path.resolve(__dirname, './dist');

gulp.task('watch', function() {
    gulp.watch(all_scss_glob, ['sass-dev']);
    gulp.watch([
        templates_with_translated_strings_glob,
        javascript_with_translated_strings_glob
    ], ['gettext-extract']);
});

gulp.task('build', function(cb) {
    return runSequence([
        'copy-assets',
        'sass-prod'
    ], cb);
});

gulp.task('gettext-extract', function() {
    return gulp.src([
        templates_with_translated_strings_glob,
        javascript_with_translated_strings_glob
    ], { base: '.' })
        .pipe(gettext.extract('template.pot', {
            lineNumbers: false
        }))
        .pipe(gulp.dest('po/'));
});

gulp.task('sass-dev', function() {
    return gulp.src(main_scss_file)
        .pipe(sass({
            sourceMap     : true,
            sourceMapEmbed: true,
            outputStyle   : 'expanded'
        }))
        .pipe(gulp.dest(build_dir));
});

gulp.task('sass-prod', function() {
    return gulp.src(main_scss_file)
        .pipe(sass({
            sourceMap  : false,
            outputStyle: 'compressed'
        }))
        .pipe(gulp.dest(build_dir));
});

gulp.task('copy-assets', function() {
    var assets = [].concat(vendor_assets_files);
    assets.push(assets_glob);

    return gulp.src(assets)
        .pipe(gulp.dest(build_dir));
});
