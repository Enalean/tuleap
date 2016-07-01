var gulp   = require('gulp');
var concat = require('gulp-concat');

var version = require("fs").readFileSync("VERSION", "utf8").trim();

var jsFiles = [
    'www/scripts/TrackerReports.js',
    'www/scripts/TrackerEmailCopyPaste.js',
    'www/scripts/TrackerReportsSaveAsModal.js',
    'www/scripts/TrackerBinds.js',
    'www/scripts/ReorderColumns.js',
    'www/scripts/TrackerTextboxLists.js',
    'www/scripts/TrackerAdminFields.js',
    'www/scripts/TrackerArtifact.js',
    'www/scripts/TrackerArtifactEmailActions.js',
    'www/scripts/TrackerArtifactLink.js',
    'www/scripts/TrackerCreate.js',
    'www/scripts/TrackerFormElementFieldPermissions.js',
    'www/scripts/TrackerDateReminderForms.js',
    'www/scripts/TrackerTriggers.js',
    'www/scripts/SubmissionKeeper.js',
    'www/scripts/TrackerFieldDependencies.js',
    'www/scripts/TrackerRichTextEditor.js',
    'www/scripts/artifactChildren.js',
    'www/scripts/load-artifactChildren.js',
    'www/scripts/modal-in-place.js',
    'www/scripts/TrackerArtifactEditionSwitcher.js',
    'www/scripts/FixAggregatesHeaderHeight.js',
    'www/scripts/TrackerSettings.js',
    'www/scripts/TrackerCollapseFieldset.js',
    'www/scripts/TrackerArtifactReferences.js',
    'www/scripts/CopyArtifact.js',
    'www/scripts/tracker-report-nature-column.js'
],
jsDest = 'www/assets/';

gulp.task('concat', function() {
    return gulp.src(jsFiles)
        .pipe(concat('tracker.' + version + '.js'))
        .pipe(gulp.dest(jsDest));
});

gulp.task('watch',function() {
    gulp.watch(jsFiles, ['concat']);
});

gulp.task('build',  ['concat']);
gulp.task('default', ['build']);
