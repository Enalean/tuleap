var gulp   = require('gulp');
var concat = require('gulp-concat');

var version = require("fs").readFileSync("VERSION", "utf8").trim();

var jsFiles = [
    'www/scripts/graphs.js',
    'www/scripts/graphs-pie.js',
    'www/scripts/graphs-bar.js',
    'www/scripts/graphs-groupedbar.js',
    'www/scripts/loadGraphs.js'
],
jsDest = 'www/assets/';

gulp.task('concat', function() {
    return gulp.src(jsFiles)
        .pipe(concat('graphontrackersv5.' + version + '.js'))
        .pipe(gulp.dest(jsDest));
});

gulp.task('watch',function() {
    gulp.watch(jsFiles, ['concat']);
});

gulp.task('build',  ['concat']);
gulp.task('default', ['build']);
