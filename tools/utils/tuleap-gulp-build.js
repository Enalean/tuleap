'use strict';

var gulp        = require('gulp'),
    merge       = require('merge2'),
    map         = require('lodash.map'),
    concat      = require('gulp-concat'),
    rev         = require('gulp-rev'),
    del         = require('del'),
    fs          = require('fs'),
    path        = require('path'),
    runSequence = require('run-sequence');

var component_builder = require('./component-builder.js');
var sass_builder      = require('./sass-builder.js');

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
    return merged_stream
        .pipe(rev())
        .pipe(gulp.dest(target_dir))
        .pipe(rev.manifest(path.join(target_dir, 'manifest.json'), {
            base : target_dir,
            merge: true
        }))
        .pipe(gulp.dest(target_dir));
}

function declare_plugin_tasks(asset_dir) {
    var javascript_tasks = [],
        sass_tasks       = [],
        scss_lint_tasks  = [],
        all_plugins_tasks    = [],
        components_tasks = [];

    get_all_plugins_from_manifests().forEach(function (plugin) {
        var name = plugin['name'],
            base_dir = path.join('plugins', name);

        var plugin_tasks        = [];
        var plugin_dependencies = [];

        var task_name = '';

        var clean_js_task_name = 'clean-js-' + name;
        gulp.task(clean_js_task_name, function () {
            return del(path.join(base_dir, asset_dir));
        });

        if ('components' in plugin) {
            task_name = 'components-' + name;

            component_builder.installAndBuildNpmComponents(
                plugin['components'],
                task_name,
                [clean_js_task_name]
            );
            components_tasks.push(task_name);
            plugin_dependencies.push(task_name);
        }

        if ('javascript' in plugin) {
            task_name = 'js-' + name;

            // If there are components, they already cleaned and added new stuff
            // in assets folder. We should not clean it twice.
            var js_dependencies = [];
            if (components_tasks.length === 0) {
                js_dependencies.push(clean_js_task_name);
            }

            gulp.task(task_name, js_dependencies, function () {
                return concat_files_plugin(base_dir, name + '.js', plugin['javascript'], asset_dir);
            });

            plugin_tasks.push(task_name);
            javascript_tasks.push(task_name);
        }

        if ('themes' in plugin) {
            sass_builder.lintSass('scss-lint-' + name, base_dir, plugin);
            scss_lint_tasks.push('scss-lint-' + name);

            task_name = 'sass-' + name;
            sass_builder.cleanAndBuildSass(task_name, base_dir, plugin);

            plugin_tasks.push(task_name);
            sass_tasks.push(task_name);
        }

        gulp.task('build-plugin-' + name, plugin_dependencies, function(callback) {
            runSequence(plugin_tasks, callback);
        });
        all_plugins_tasks.push('build-plugin-' + name);
    });

    gulp.task('components-plugins', components_tasks);
    gulp.task('js-plugins', javascript_tasks);
    gulp.task('sass-plugins', sass_tasks);
    gulp.task('scss-lint-plugins', scss_lint_tasks);
    gulp.task('plugins', all_plugins_tasks);
}

function concat_files_plugin(base_dir, name, files, asset_dir) {
    var assets_paths = path.join(base_dir, asset_dir);

    return gulp.src(files, {cwd: base_dir})
        .pipe(concat(name))
        .pipe(rev())
        .pipe(gulp.dest(assets_paths))
        .pipe(rev.manifest(path.join(assets_paths, 'manifest.json'), {
            base : assets_paths,
            merge: true
        }))
        .pipe(gulp.dest(assets_paths));
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
    watch_plugins       : watch_plugins,
    concat_core_js      : concat_core_js,
    declare_plugin_tasks: declare_plugin_tasks
};
