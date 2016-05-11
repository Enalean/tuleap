/**
 * This file/module contains all configuration for the build process.
 */
module.exports = {
    /**
     * The `build_dir` folder is where our projects are compiled during
     * development and the `compile_dir` folder is where our app resides once it's
     * completely built.
     */
    build_dir: 'build',
    compile_dir: 'bin',
    vendor_dir: 'bower_components',

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
            '!src/**/*-controller.js',
            '!src/**/*-config.js',
            '!src/**/*-directive.js',
            '!src/**/*-factory.js',
            '!src/**/*-filter.js',
            '!src/**/*-run.js'
        ],
        js: [
            'src/**/*-service.js',
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

        html: ['src/index.html'],
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
     *
     * The `vendor_files.css` property holds any CSS files to be automatically
     * included in our app.
     */
    vendor_files: {
        js: [
            'bower_components/angular/angular.js',
            'bower_components/ng-lodash/build/ng-lodash.min.js',
            'bower_components/angular-ui-router/release/angular-ui-router.js',
            'bower_components/ui-select/dist/select.min.js',
            'bower_components/angular-gettext/dist/angular-gettext.min.js',
            'bower_components/angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            'bower_components/angular-sanitize/angular-sanitize.min.js',
            'bower_components/moment/min/moment.min.js',
            'bower_components/moment/locale/fr.js',
            'bower_components/angular-moment/angular-moment.min.js',
            'bower_components/codemirror/lib/codemirror.js',
            'bower_components/codemirror/addon/mode/simple.js',
            'bower_components/codemirror/mode/**/*.js'
        ],
        css: [
            'bower_components/ui-select/dist/select.min.css',
            'bower_components/codemirror/lib/codemirror.css'
        ],
        assets: [
        ]
    }
};
