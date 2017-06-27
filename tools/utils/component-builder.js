var gulp         = require('gulp');
var runSequence  = require('run-sequence');
var map          = require('lodash.map');
var partialRight = require('lodash.partialright');
var spread       = require('lodash.spread');
var readPkg      = require('read-pkg');
var path         = require('path');
var exec         = require('child_process').exec;
var spawnSync    = require('child_process').spawnSync;

function verifyPackageJsonFile(component_path) {
    var package_json_path = path.join(component_path, 'package.json');

    return readPkg(package_json_path)
        .then(function (pkg) {
            if (! pkg.name) {
                throw new Error("package.json file should have a 'name' " + package_json_path);
            }

            if (! pkg.scripts || ! pkg.scripts.build) {
                throw new Error("package.json file should have a 'build' script " + package_json_path);
            }

            return {
                name: pkg.name,
                path: component_path
            };
        })
        .catch(function() {
            throw new Error("package.json file could not be found at " + package_json_path);
        });
}

function findComponentsWithPackageAndBuildScript(component_paths) {
    var promises = map(component_paths, function (component_path) {
        return verifyPackageJsonFile(component_path);
    });

    return Promise.all(promises);
}

function installNpmComponent(component) {
    var task_name = 'install-' + component.name;
    gulp.task(task_name, function (cb) {
        exec('npm install', {
            cwd: component.path
        }, cb);
    });

    return task_name;
}

function buildNpmComponent(component, dependent_tasks) {
    var task_name = 'build-' + component.name;
    gulp.task(task_name, dependent_tasks, function () {
        return spawnSync('npm', ['run', 'build'], {
            stdio: 'inherit',
            cwd  : component.path
        });
    });

    return task_name;
}

function installBowerComponent(component, dependent_tasks) {
    var task_name = 'bower-install-' + component.name;
    gulp.task(task_name, dependent_tasks, function (cb) {
        exec('npm run bower install', {
            cwd: component.path
        }, cb);
    });

    return task_name;
}

function installAndBuildNpmComponents(component_paths, components_task_name) {
    var install_tasks = [],
        build_tasks   = [];

    var promise = findComponentsWithPackageAndBuildScript(component_paths).then(function (components) {
        components.forEach(function(component) {
            var install_task_name = installNpmComponent(component);
            var build_task_name   = buildNpmComponent(component, [install_task_name]);
            install_tasks.push(install_task_name);
            build_tasks.push(build_task_name);
        });
    });

    gulp.task(components_task_name, ['clean-js-core'], function(cb) {
        promise.then(function() {
            runSequence(install_tasks.concat(build_tasks), cb);
        }).catch(function (error) {
            cb(error);
        });
    });
}

function installAndBuildBowerComponents(component_paths, components_task_name) {
    var build_tasks = [];

    var promise = findComponentsWithPackageAndBuildScript(component_paths).then(function (components) {
        components.forEach(function(component) {
            var install_task_name       = installNpmComponent(component);
            var bower_install_task_name = installBowerComponent(component, [install_task_name]);
            var build_task_name         = buildNpmComponent(component, [install_task_name, bower_install_task_name]);
            build_tasks.push(build_task_name);
        });
    });

    gulp.task(components_task_name, function(cb) {
        promise.then(function() {
            return runBuildTasksInTheExactOrderTheyArePassed(build_tasks, cb);
        }).catch(function (error) {
            cb(error);
        });
    });
}

function runBuildTasksInTheExactOrderTheyArePassed(build_tasks, gulpCallback) {
    var runSequenceWithCallback = partialRight(runSequence, gulpCallback);
    return spread(runSequenceWithCallback)(build_tasks);
}

module.exports = {
    installAndBuildBowerComponents: installAndBuildBowerComponents,
    installAndBuildNpmComponents  : installAndBuildNpmComponents
};
