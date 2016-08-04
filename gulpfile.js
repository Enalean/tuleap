'use strict';

var gulp    = require('gulp'),
    exec    = require('gulp-exec'),
    install = require('gulp-install'),
    concat  = require('gulp-concat'),
    rev     = require('gulp-rev'),
    del     = require('del'),
    fs      = require('fs'),
    path    = require('path'),
    plugins = getAllPluginsWithGulpfile(),
    version = require("fs").readFileSync("VERSION", "utf8").trim(),
    fat_combined_files = [
        'src/www/scripts/polyphills/json2.js',
        'src/www/scripts/polyphills/storage.js',
        'src/www/scripts/prototype/prototype.js',
        'src/www/scripts/protocheck/protocheck.js',
        'src/www/scripts/scriptaculous/scriptaculous.js',
        'src/www/scripts/scriptaculous/builder.js',
        'src/www/scripts/scriptaculous/effects.js',
        'src/www/scripts/scriptaculous/dragdrop.js',
        'src/www/scripts/scriptaculous/controls.js',
        'src/www/scripts/scriptaculous/slider.js',
        'src/www/scripts/scriptaculous/sound.js',
        'src/www/scripts/jquery/jquery-1.9.1.min.js',
        'src/www/scripts/jquery/jquery-ui.min.js',
        'src/www/scripts/jquery/jquery-noconflict.js',
        'src/www/scripts/tuleap/browser-compatibility.js',
        'src/www/scripts/tuleap/project-history.js',
        'src/www/scripts/bootstrap/bootstrap-dropdown.js',
        'src/www/scripts/bootstrap/bootstrap-button.js',
        'src/www/scripts/bootstrap/bootstrap-modal.js',
        'src/www/scripts/bootstrap/bootstrap-collapse.js',
        'src/www/scripts/bootstrap/bootstrap-tooltip.js',
        'src/www/scripts/bootstrap/bootstrap-tooltip-fix-prototypejs-conflict.js',
        'src/www/scripts/bootstrap/bootstrap-popover.js',
        'src/www/scripts/bootstrap/bootstrap-select/bootstrap-select.js',
        'src/www/scripts/bootstrap/bootstrap-tour/bootstrap-tour.min.js',
        'src/www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js',
        'src/www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.fr.js',
        'src/www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker-fix-prototypejs-conflict.js',
        'src/www/scripts/jscrollpane/jquery.mousewheel.js',
        'src/www/scripts/jscrollpane/jquery.jscrollpane.min.js',
        'src/www/scripts/select2/select2.min.js',
        'src/www/scripts/vendor/at/js/caret.min.js',
        'src/www/scripts/vendor/at/js/atwho.min.js',
        'src/www/scripts/viewportchecker/viewport-checker.js',
        'src/www/scripts/clamp.js',
        'src/www/scripts/codendi/common.js',
        'src/www/scripts/tuleap/massmail_initialize_ckeditor.js',
        'src/www/scripts/tuleap/is-at-top.js',
        'src/www/scripts/tuleap/get-style-class-property.js',
        'src/www/scripts/tuleap/listFilter.js',
        'src/www/scripts/codendi/feedback.js',
        'src/www/scripts/codendi/CreateProject.js',
        'src/www/scripts/codendi/cross_references.js',
        'src/www/scripts/codendi/Tooltip.js',
        'src/www/scripts/codendi/Tooltip-loader.js',
        'src/www/scripts/codendi/Toggler.js',
        'src/www/scripts/codendi/LayoutManager.js',
        'src/www/scripts/codendi/DropDownPanel.js',
        'src/www/scripts/codendi/colorpicker.js',
        'src/www/scripts/autocomplete.js',
        'src/www/scripts/textboxlist/multiselect.js',
        'src/www/scripts/tablekit/tablekit.js',
        'src/www/scripts/lytebox/lytebox.js',
        'src/www/scripts/lightwindow/lightwindow.js',
        'src/www/scripts/tuleap/escaper.js',
        'src/www/scripts/codendi/RichTextEditor.js',
        'src/www/scripts/codendi/Tracker.js',
        'src/www/scripts/codendi/TreeNode.js',
        'src/www/scripts/tuleap/tuleap-modal.js',
        'src/www/scripts/tuleap/tuleap-tours.js',
        'src/www/scripts/tuleap/tuleap-standard-homepage.js',
        'src/www/scripts/placeholder/jquery.placeholder.js',
        'src/www/scripts/tuleap/datetimepicker.js',
        'src/www/scripts/tuleap/svn.js',
        'src/www/scripts/tuleap/trovecat.js',
        'src/www/scripts/tuleap/account-maintenance.js',
        'src/www/scripts/tuleap/search.js',
        'src/www/scripts/tuleap/tuleap-mention.js',
        'src/www/scripts/tuleap/project-privacy-tooltip.js',
        'src/www/scripts/tuleap/massmail_project_members.js',
        'src/www/scripts/tuleap/manage-allowed-projects-on-resource.js',
        'src/www/scripts/tuleap/textarea_rte.js',
        'src/www/scripts/admin/system_events.js',
        'src/www/scripts/d3/d3.min.js'
    ],
    subset_combined_files = [
        'src/www/scripts/jquery/jquery-2.1.1.min.js',
        'src/www/scripts/bootstrap/bootstrap-tooltip.js',
        'src/www/scripts/bootstrap/bootstrap-popover.js',
        'src/www/scripts/bootstrap/bootstrap-button.js',
        'src/www/scripts/tuleap/project-privacy-tooltip.js'
    ],
    subset_combined_flamingparrot_files = [
        'src/www/scripts/bootstrap/bootstrap-dropdown.js',
        'src/www/scripts/bootstrap/bootstrap-modal.js',
        'src/www/scripts/jscrollpane/jquery.mousewheel.js',
        'src/www/scripts/jscrollpane/jquery.jscrollpane.min.js',
        'src/www/scripts/tuleap/listFilter.js',
        'src/www/scripts/codendi/Tooltip.js'
    ],
    flaming_parrot_files = [
        'src/www/themes/FlamingParrot/js/navbar.js',
        'src/www/themes/FlamingParrot/js/sidebar.js',
        'src/www/themes/FlamingParrot/js/motd.js',
        'src/www/themes/FlamingParrot/js/keymaster/keymaster.js',
        'src/www/themes/FlamingParrot/js/keymaster-sequence/keymaster.sequence.min.js',
        'src/www/themes/FlamingParrot/js/keyboard-navigation.js'
    ],
    asset_dir = 'src/www/assets/';

gulp.task('default', ['build']);

gulp.task('clean-core', function() {
    del(asset_dir);
});

gulp.task('clean-plugins', plugins.map(function (plugin) { return 'clean-'+ plugin; }));

gulp.task('clean', ['clean-core', 'clean-plugins']);

gulp.task('concat', ['clean-core'], function() {
    concatFiles('tuleap', fat_combined_files);
    concatFiles('tuleap_subset', subset_combined_files);
    concatFiles('tuleap_subset_flamingparrot', subset_combined_files.concat(subset_combined_flamingparrot_files));
    concatFiles('flamingparrot', flaming_parrot_files);
});

gulp.task('build-plugins', plugins.map(function (plugin) { return 'build-'+ plugin; }));

gulp.task('build', ['concat', 'build-plugins']);

gulp.task('install', plugins.map(function (plugin) { return 'install-'+ plugin; }));

plugins.forEach(function (plugin) {
    gulp.task('install-'+ plugin, function () {
        return installInPlugin(plugin);
    });

    gulp.task('build-'+ plugin, ['install-'+ plugin], function () {
        return runInPlugin(plugin, 'build');
    });

    gulp.task('clean-'+ plugin, ['install-'+ plugin], function () {
        return runInPlugin(plugin, 'clean');
    });
});

function concatFiles(name, files) {
    gulp.src(files)
        .pipe(concat(name+'.js'))
        .pipe(rev())
        .pipe(gulp.dest(asset_dir))
        .pipe(rev.manifest({
            path: asset_dir + '/manifest.json',
            base: asset_dir,
            merge: true
        }))
        .pipe(gulp.dest(asset_dir));
}

function installInPlugin(plugin) {
    return gulp.src('./plugins/'+ plugin +'/package.json')
        .pipe(gulp.dest('./plugins/'+ plugin +'/'))
        .pipe(install());
}

function runInPlugin(plugin, task) {
    return gulp.src('plugins/'+ plugin +'/gulpfile.js')
        .pipe(exec('node_modules/.bin/gulp --gulpfile=<%= file.path %> '+task));
}

function getAllPluginsWithGulpfile() {
    var plugins_path = './plugins';

    return fs.readdirSync(plugins_path).filter(function (file) {
            try {
                return fs.statSync(path.join(plugins_path, file, 'gulpfile.js')).isFile();
            } catch (e) {
                return false;
            }
        });
}
