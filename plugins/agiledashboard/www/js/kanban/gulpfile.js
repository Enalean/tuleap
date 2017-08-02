var Server      = require('karma').Server;
var path        = require('path');
var gulp        = require('gulp');
var del         = require('del');
var gettext     = require('gulp-angular-gettext');
var runSequence = require('run-sequence').use(gulp);

var templates_with_translated_strings_glob  = 'src/app/**/*.tpl.html';
var javascript_with_translated_strings_glob = 'src/app/**/*.js';
var old_coverage_glob = './coverage/*';
var build_dir         = path.resolve(__dirname, './dist');

// Cleaning tasks
gulp.task('clean-coverage', function() {
    return del(old_coverage_glob);
});

gulp.task('clean-assets', function () {
    return del(build_dir);
});

gulp.task('watch', function(cb) {
    gulp.watch([
        templates_with_translated_strings_glob,
        javascript_with_translated_strings_glob
    ], ['gettext-extract']);
    return runSequence('test-continuous', cb);
});

gulp.task('build', ['clean-assets']);

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

gulp.task('test', function(done) {
    new Server({
        configFile   : path.resolve(__dirname, './karma.conf.js'),
        singleRun    : true,
        reporters    : ['dots', 'junit'],
        junitReporter: {
            outputFile    : 'test-results.xml',
            useBrowserName: false
        }
    }, done).start();
});

gulp.task('test-continuous', function(done) {
    new Server({
        configFile: path.resolve(__dirname, './karma.conf.js'),
        singleRun : false,
        reporters : ['dots']
    }, done).start();
});

gulp.task('coverage', ['clean-coverage'], function(done) {
    new Server({
        configFile              : path.resolve(__dirname, './karma.conf.js'),
        singleRun               : true,
        reporters               : ['dots', 'coverage-istanbul'],
        coverageIstanbulReporter: {
            reports              : ['html'],
            dir                  : path.resolve(__dirname, './coverage'),
            fixWebpackSourcePaths: true
        }
    }, done).start();
});
