var gulp   = require('gulp');
var concat = require('gulp-concat');
var version = require("fs").readFileSync("VERSION", "utf8").trim();

var jsFiles = [
    '../../src/www/scripts/behaviour/behaviour.js',
    'www/scripts/docman.js',
    'www/scripts/embedded_file.js',
    'www/scripts/ApprovalTableReminder.js'
],
jsDest = 'www/assets/';

gulp.task('concat', function() {
    return gulp.src(jsFiles)
        .pipe(concat('docman.' + version + '.js'))
        .pipe(gulp.dest(jsDest));
});

gulp.task('watch',function() {
    gulp.watch(jsFiles, ['concat']);
});

gulp.task('build',  ['concat']);
gulp.task('default', ['build']);
