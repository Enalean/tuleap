const gulp        = require('gulp');
const runSequence = require('run-sequence');
const map         = require('lodash.map');
const readPkg     = require('read-pkg');
const path        = require('path');
const exec        = require('child_process').exec;
const spawn       = require('child_process').spawn;

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
    gulp.task(task_name, function (callback) {
        exec('npm install', {
            cwd: component.path
        }, function(error) {
            if (error) {
                return callback(error);
            }
            callback();
        });
    });

    return task_name;
}

function buildNpmComponent(component, dependent_tasks) {
    var task_name = 'build-' + component.name;
    gulp.task(task_name, dependent_tasks, function (callback) {
        var child_process = spawn('npm', ['run', 'build'], {
            stdio: 'inherit',
            cwd  : component.path
        });

        child_process.on('close', function(code) {
            if (code !== 0) {
                return callback(code);
            }
            callback();
        })
    });

    return task_name;
}

function installBowerComponent(component, dependent_tasks) {
    var task_name = 'bower-install-' + component.name;
    gulp.task(task_name, dependent_tasks, function (callback) {
        exec('npm run bower install', {
            cwd: component.path
        }, function(error) {
            if (error) {
                return callback(error);
            }
            callback();
        });
    });

    return task_name;
}

function installAndBuildNpmComponents(component_paths, components_task_name, dependent_tasks) {
    const build_tasks = [];

    var promise = findComponentsWithPackageAndBuildScript(component_paths).then(function (components) {
        components.forEach(function(component) {
            var install_task_name = installNpmComponent(component);
            var build_task_name   = buildNpmComponent(component, [install_task_name]);
            build_tasks.push(build_task_name);
        });
    });

    gulp.task(components_task_name, dependent_tasks, function(callback) {
        promise.then(() => {
            return runSequence(...build_tasks, callback);
        }).catch(error => callback(error));
    });
}

function installAndBuildBowerComponents(component_paths, components_task_name, dependent_tasks) {
    var build_tasks = [];

    var promise = findComponentsWithPackageAndBuildScript(component_paths).then(function (components) {
        components.forEach(function(component) {
            var install_task_name       = installNpmComponent(component);
            var bower_install_task_name = installBowerComponent(component, [install_task_name]);
            var build_task_name         = buildNpmComponent(component, [install_task_name, bower_install_task_name]);
            build_tasks.push(build_task_name);
        });
    });

    gulp.task(components_task_name, dependent_tasks, function(callback) {
        promise.then(() => {
            return runSequence(...build_tasks, callback);
        }).catch(error => callback(error));
    });
}

module.exports = {
    installAndBuildBowerComponents,
    installAndBuildNpmComponents
};
