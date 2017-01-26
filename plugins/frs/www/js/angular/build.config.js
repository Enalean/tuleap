/**
 * This file/module contains all configuration for the build process.
 */
module.exports = {
    /**
     * The `build_dir` folder is where our projects are compiled during
     * development and the `compile_dir` folder is where our app resides once it's
     * completely built.
     */
    build_dir  : 'build',
    compile_dir: 'bin',

    /**
     * This is a collection of file patterns that refer to our app code (the
     * stuff in `src/`). These file paths are used in the configuration of
     * build tasks. `js` is all project javascript, less tests. `ctpl` contains
     * our reusable components' (`src/common`) template HTML files, while
     * `atpl` contains the same, but for our app's code. `html` is just our
     * main HTML file, `less` is our main stylesheet, and `unit` contains our
     * app's unit tests.
     */
    app_files: {
        modules: [
            'src/**/*.js',
            '!src/**/*.spec.js',
            '!src/**/*-service.js',
            '!src/**/*-value.js',
            '!src/**/*-constant.js',
            '!src/**/*-controller.js',
            '!src/**/*-config.js',
            '!src/**/*-directive.js',
            '!src/**/*-factory.js',
            '!src/**/*-filter.js',
            '!src/**/*-run.js'
        ],
        js: [
            'src/**/*-service.js',
            'src/**/*-value.js',
            'src/**/*-constant.js',
            'src/**/*-controller.js',
            'src/**/*-config.js',
            'src/**/*-directive.js',
            'src/**/*-factory.js',
            'src/**/*-filter.js',
            'src/**/*-run.js',
            '!src/**/*.spec.js'
        ],
        jsunit: ['src/**/*.spec.js'],

        atpl: ['src/app/**/*.tpl.html'],
        scss: 'src/app/main.scss'
    },

    /**
     * This is the same as `app_files`, except it contains patterns that
     * reference vendor code (`vendor/`) that we need to place into the build
     * process somewhere. While the `app_files` property ensures all
     * standardized files are collected for compilation, it is the user's job
     * to ensure non-standardized (i.e. vendor-related) files are handled
     * appropriately in `vendor_files.js`.
     *
     * The `vendor_files.js` property holds files to be automatically
     * concatenated and minified with our project source files.
     */
    vendor_files: {
        js: [
            'vendor/angular/angular.js',
            'vendor/angular-sanitize/angular-sanitize.js',
            'vendor/ng-lodash/build/ng-lodash.js',
            'vendor/angular-gettext/dist/angular-gettext.js',
            'vendor/angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            'vendor/angular-filter/dist/angular-filter.js',
            'vendor/showdown/dist/showdown.js',
            'vendor/ng-showdown/dist/ng-showdown.js',
            'vendor/angular-ui-router/release/angular-ui-router.js'
        ],
        assets: [
        ]
    }
};
