var gulp   = require('gulp');
var concat = require('gulp-concat');

var version = require("fs").readFileSync("VERSION", "utf8").trim();

var jsFiles = [
    'www/js/display-angular-feedback.js',
    'www/js/MilestoneContent.js',
    'www/js/planning-view.js',
    'www/js/ContentFilter.js',
    'www/js/home.js'
],
jsDest = 'www/assets/';

gulp.task('concat', function() {
    return gulp.src(jsFiles)
        .pipe(concat('agiledashboard.' + version + '.js'))
        .pipe(gulp.dest(jsDest));
});

gulp.task('watch',function() {
    gulp.watch(jsFiles, ['concat']);
});

gulp.task('build',  ['concat']);
gulp.task('default', ['build']);
