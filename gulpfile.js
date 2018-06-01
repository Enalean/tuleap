const gulp        = require('gulp');
const path        = require('path');
const del         = require('del');
const runSequence = require('run-sequence');

const tuleap            = require('./tools/utils/tuleap-gulp-build');
const component_builder = require('./tools/utils/component-builder.js');
const sass_builder      = require('./tools/utils/sass-builder.js');

const core_build_manifest = require('./build-manifest.json');

const fat_combined_files = [
        'src/www/scripts/prototype/prototype.js',
        'src/www/scripts/protocheck/protocheck.js',
        'src/www/scripts/scriptaculous/scriptaculous.js',
        'src/www/scripts/scriptaculous/builder.js',
        'src/www/scripts/scriptaculous/effects.js',
        'src/www/scripts/scriptaculous/dragdrop.js',
        'src/www/scripts/scriptaculous/controls.js',
        'src/www/scripts/scriptaculous/slider.js',
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
        'src/www/scripts/codendi/DropDownPanel.js',
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
        'src/www/scripts/tuleap/textarea_rte.js',
        'src/www/scripts/tuleap/tuleap-ckeditor-toolbar.js',
        'src/www/scripts/tuleap/project-visibility.js',
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
        'src/www/scripts/bootstrap/bootstrap-tour/bootstrap-tour.min.js',
        'src/www/scripts/jscrollpane/jquery.mousewheel.js',
        'src/www/scripts/jscrollpane/jquery.jscrollpane.min.js',
        'src/www/scripts/tuleap/tuleap-tours.js',
        'src/www/scripts/tuleap/listFilter.js',
        'src/www/scripts/codendi/Tooltip.js'
    ],
    select2_scss = {
        themes: {
            "common": {
                files: [
                    'src/www/scripts/select2/select2.scss'
                ],
                target_dir: 'src/www/scripts/select2'
            }
        }
    },
    bower_app_paths = [
        'plugins/frs/www/js/angular',
        'plugins/pullrequest/www/scripts/'
    ],
    asset_dir = 'www/assets';

tuleap.declare_plugin_tasks(asset_dir);
component_builder.installAndBuildNpmComponents(core_build_manifest.components, 'components-core', ['clean-js-core']);
component_builder.installAndBuildBowerComponents(bower_app_paths, 'bower-apps');
const base_dir = '.';
sass_builder.cleanAndBuildSass('sass-core-select2', base_dir, select2_scss);
sass_builder.cleanAndBuildSass('sass-core-themes', base_dir, core_build_manifest);
sass_builder.lintSass('scss-lint-core-select2', base_dir, select2_scss);
sass_builder.lintSass('scss-lint-core', base_dir, core_build_manifest);

/**
 * Javascript
 */

gulp.task('clean-js-core', function() {
    return del('src/' + asset_dir + '/*');
});

gulp.task('js-core', function() {
    const target_dir = path.join('src', asset_dir);
    const files_hash = {
        tuleap                     : fat_combined_files,
        tuleap_subset              : subset_combined_files,
        tuleap_subset_flamingparrot: subset_combined_files.concat(subset_combined_flamingparrot_files),
    };

    return tuleap.concat_core_js(files_hash, target_dir);
});

gulp.task('js', ['js-core', 'js-plugins']);

gulp.task('scss-lint', [
    'scss-lint-core-select2',
    'scss-lint-core',
    'scss-lint-plugins'
]);

gulp.task('sass-core', [
    'sass-core-select2',
    'sass-core-themes',
]);

gulp.task('sass', ['sass-core', 'sass-plugins']);

gulp.task('components', ['components-core', 'components-plugins']);

/**
 * Global
 */

gulp.task('watch', function() {
    gulp.watch(
        fat_combined_files
            .concat(subset_combined_files)
            .concat(subset_combined_flamingparrot_files),
        ['js-core']
    );

    gulp.watch(
        core_build_manifest.themes.common.files
            .concat(select2_scss.themes.common.files)
            .concat(core_build_manifest.themes.FlamingParrot.files)
            .concat(core_build_manifest.themes.FlamingParrot.watched_includes)
            .concat(core_build_manifest.themes.BurningParrot.files)
            .concat(core_build_manifest.themes.BurningParrot.watched_includes),
        ['sass-core']
    );

    tuleap.watch_plugins();
});

gulp.task('core', ['js-core', 'sass-core']);

gulp.task('build', ['components-core', 'bower-apps'], function(callback) {
    runSequence('core', 'plugins', callback);
});

gulp.task('default', ['build']);
