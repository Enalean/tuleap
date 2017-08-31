var gulp        = require('gulp');
var scss_lint   = require('gulp-scss-lint');
var sass        = require('gulp-sass');
var path        = require('path');
var gulp_util   = require('gulp-util');
var del         = require('del');
var merge       = require('merge2');

function cleanSass(base_dir, scss_files) {
    var css_files = scss_files.map(function(file) {
        var filename = path.basename(file, path.extname(file)) + '.css',
            css_path = path.join(base_dir, path.dirname(file), filename);
        return css_path;
    });
    return del(css_files)
}

function buildSass(base_dir, scss_hash, callback) {
    var sass_options = { outputStyle: 'compressed' };

    if ('includes' in scss_hash) {
        sass_options.includePaths = scss_hash.includes.map(function (p) { return path.join(base_dir, p);});
    }

    return gulp.src(scss_hash.files, { cwd: base_dir })
        .pipe(
            sass(sass_options).on('error', function(error) {
                var message = new gulp_util.PluginError('sass', error.messageFormatted);
                return callback(message);
            })
        )
        .pipe(gulp.dest(path.join(base_dir, scss_hash.target_dir)));
}

function cleanAndBuildSass(sass_task_name, base_dir, scss_hash, dependent_tasks) {
    var all_theme_names = Object.keys(scss_hash.themes);
    var clean_task_name = 'clean-' + sass_task_name;

    gulp.task(clean_task_name, function() {
        var promises = all_theme_names.map(function (theme) {
            return cleanSass(base_dir, scss_hash.themes[theme].files);
        });

        return Promise.all(promises);
    });

    var dependent_tasks_array = dependent_tasks || [];
    var dependencies          = [clean_task_name].concat(dependent_tasks_array);
    gulp.task(sass_task_name, dependencies, function(callback) {
        var streams = all_theme_names.map(function (theme) {
            return buildSass(base_dir, scss_hash.themes[theme], callback);
        });

        return merge(streams);
    });
}

function lintSass(lint_sass_task_name, base_dir, scss_hash) {
    gulp.task(lint_sass_task_name, function() {
        Object.keys(scss_hash.themes).forEach(function (theme) {
            return gulp.src(scss_hash.themes[theme].files, { cwd: base_dir })
                .pipe(scss_lint({
                    config : '.scss-lint.yml'
                }));
        });
    });
}

module.exports = {
    lintSass         : lintSass,
    cleanAndBuildSass: cleanAndBuildSass
}
