'use strict';

var gulp        = require('gulp'),
    merge       = require('merge2'),
    map         = require('lodash.map'),
    concat      = require('gulp-concat'),
    rev         = require('gulp-rev'),
    del         = require('del'),
    fs          = require('fs'),
    path        = require('path'),
    scss_lint   = require('gulp-scss-lint'),
    sass        = require('gulp-sass');

function get_all_plugins_from_manifests() {
    var plugins_path = './plugins';

    return fs.readdirSync(plugins_path).filter(function (file) {
            try {
                var manifest_path = path.join(plugins_path, file, 'build-manifest.json');
                return fs.statSync(manifest_path).isFile();
            } catch (e) {
                return false;
            }
        }).map(function (plugin) {
            var manifest_path = path.join(plugins_path, plugin, 'build-manifest.json');
            return JSON.parse(fs.readFileSync(manifest_path), 'utf8');
        });
}

function concat_all_js_files(files_hash) {
    var streams = map(files_hash, function(files_to_concat, dest_file_name) {
        return gulp.src(files_to_concat)
            .pipe(concat(dest_file_name + '.js'));
    });

    return merge(streams);
}

function concat_core_js(files_hash, target_dir) {
    var merged_stream = concat_all_js_files(files_hash);

    return merged_stream.pipe(rev())
        .pipe(gulp.dest(target_dir))
        .pipe(rev.manifest({
            path : target_dir + '/manifest.json',
            base : target_dir,
            merge: true
        }))
        .pipe(gulp.dest(target_dir));
}

function declare_plugin_tasks(asset_dir) {
    var javascript_tasks = [],
        clean_tasks      = [],
        sass_tasks       = [],
        scss_lint_tasks  = [],
        plugins_tasks    = [];

    get_all_plugins_from_manifests().forEach(function (plugin) {
        var name = plugin['name'],
            base_dir = path.join('plugins', name);

        var plugin_tasks = [];
        var task_name = '';

        if ('javascript' in plugin) {
            task_name = 'js-' + name;

            gulp.task('clean-' + task_name, function () {
                return del(path.join(base_dir, asset_dir));
            });

            gulp.task(task_name, ['clean-' + task_name], function () {
                return concat_files_plugin(base_dir, name + '.js', plugin['javascript'], asset_dir);
            });

            plugin_tasks.push(task_name);
            javascript_tasks.push(task_name);
            clean_tasks.push('clean-' + task_name);
        }

        if ('themes' in plugin) {
            task_name = 'sass-' + name;

            gulp.task('scss-lint-' + name, function() {
                Object.keys(plugin['themes']).forEach(function (theme) {
                    return gulp.src(plugin['themes'][theme]['files'], { cwd: base_dir })
                        .pipe(scss_lint({
                            config : '.scss-lint.yml'
                        }));
                });
            });

            gulp.task('clean-' + task_name, function () {
                Object.keys(plugin['themes']).forEach(function (theme) {
                    sass_clean(base_dir, plugin['themes'][theme]['files']);
                });
            });

            gulp.task(task_name, ['clean-' + task_name], function () {
                Object.keys(plugin['themes']).forEach(function (theme) {
                    sass_build(base_dir, plugin['themes'][theme]);
                });
            });

            plugin_tasks.push(task_name);
            sass_tasks.push(task_name);
            scss_lint_tasks.push('scss-lint-' + name);
            clean_tasks.push('clean-' + task_name);
        }

        gulp.task('build-plugin-' + name, plugin_tasks);

        plugins_tasks.push('build-plugin-' + name);
    });

    gulp.task('js-plugins', javascript_tasks);
    gulp.task('sass-plugins', sass_tasks);
    gulp.task('scss-lint-plugins', scss_lint_tasks);
    gulp.task('clean-plugins', clean_tasks);
    gulp.task('plugins', plugins_tasks);
}

function concat_files_plugin(base_dir, name, files, asset_dir) {
    return gulp.src(files, {cwd: base_dir})
        .pipe(concat(name))
        .pipe(rev())
        .pipe(gulp.dest(path.join(base_dir, asset_dir)))
        .pipe(rev.manifest('manifest.json'))
        .pipe(gulp.dest(path.join(base_dir, asset_dir)));
}

function sass_build(base_dir, scss_hash) {
    var sass_options = { outputStyle: 'compressed' };
    if ('includes' in scss_hash) {
        sass_options['includePaths'] = scss_hash['includes'].map(function (p) { return path.join(base_dir, p);});
    }

    return gulp.src(scss_hash.files, {cwd: base_dir})
        .pipe(sass(sass_options).on('error', sass.logError))
        .pipe(gulp.dest(path.join(base_dir, scss_hash.target_dir)));
}

function sass_clean(base_dir, scss_files) {
    var css_files = scss_files.map(function(file) {
        var filename = path.basename(file, path.extname(file)) + '.css',
            css_path = path.join(base_dir, path.dirname(file), filename);
        return css_path;
    });
    del(css_files);
}

function watch_plugins() {
    get_all_plugins_from_manifests().forEach(function (plugin) {
        var name = plugin['name'],
            base_dir = path.join('plugins', name);

        if ('javascript' in plugin) {
            gulp.watch(
                plugin['javascript'].map(function (file) { return path.join(base_dir, file);}),
                ['js-' + name]
            );
        }

        if ('themes' in plugin) {
            var files = [];
            Object.keys(plugin['themes']).forEach(function (theme) {
                files = files.concat(
                    plugin['themes'][theme]['files'].map(function (file) { return path.join(base_dir, file);})
                );
                var watched_includes = plugin['themes'][theme]['watched_includes'];
                if (watched_includes) {
                    files = files.concat(
                        watched_includes.map(function (file) { return path.join(base_dir, file); })
                    );
                }
            });

            gulp.watch(files, ['sass-' + name]);
        }
    });
}

module.exports = {
    sass_clean             : sass_clean,
    sass_build             : sass_build,
    watch_plugins          : watch_plugins,
    concat_core_js         : concat_core_js,
    declare_plugin_tasks   : declare_plugin_tasks
};
