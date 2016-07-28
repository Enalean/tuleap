'use strict';

var gulp    = require('gulp'),
    exec    = require('gulp-exec'),
    install = require('gulp-install'),
    fs      = require('fs'),
    path    = require('path'),
    plugins = getAllPluginsWithGulpfile();



gulp.task('default', ['build']);

gulp.task('install', plugins.map(function (plugin) { return 'install-'+ plugin; }));
gulp.task('build', plugins.map(function (plugin) { return 'build-'+ plugin; }));

plugins.forEach(function (plugin) {
    gulp.task('install-'+ plugin, function () {
        return installInPlugin(plugin);
    });

    gulp.task('build-'+ plugin, ['install-'+ plugin], function () {
        return buildInPlugin(plugin);
    });
})

function installInPlugin(plugin) {
    return gulp.src('./plugins/'+ plugin +'/package.json')
        .pipe(gulp.dest('./plugins/'+ plugin +'/'))
        .pipe(install());
}

function buildInPlugin(plugin) {
    return gulp.src('plugins/'+ plugin +'/gulpfile.js')
        .pipe(exec('node_modules/.bin/gulp --gulpfile=<%= file.path %>'));
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
