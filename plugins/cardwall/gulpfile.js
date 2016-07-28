var gulp   = require('gulp');
var concat = require('gulp-concat');

var version = require("fs").readFileSync("VERSION", "utf8").trim();

var jsFiles = [
    'www/js/ajaxInPlaceEditorExtensions.js',
    'www/js/cardwall.js',
    'www/js/script.js',
    'www/js/custom-mapping.js',
    'www/js/CardsEditInPlace.js',
    'www/js/fullscreen.js'
],
jsDest = 'www/assets/';

gulp.task('concat', function() {
    return gulp.src(jsFiles)
        .pipe(concat('cardwall.' + version + '.js'))
        .pipe(gulp.dest(jsDest));
});

gulp.task('watch',function() {
    gulp.watch(jsFiles, ['concat']);
});

gulp.task('build',  ['concat']);
gulp.task('default', ['build']);
