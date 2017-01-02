module.exports = function(grunt) {
    'use strict';

    // We explicitly load watch because of the hacks to the watch task below
    grunt.loadNpmTasks('grunt-contrib-watch');
    // Lazy-load grunt tasks automatically
    require('jit-grunt')(grunt, {
        nggettext_extract: 'grunt-angular-gettext',
        nggettext_compile: 'grunt-angular-gettext'
    });

    // Time how long tasks take. Can help when optimizing build times
    require('time-grunt')(grunt);

    /**
     * Load in our build configuration file.
     */
    var userConfig = require('./build.config.js');

    /**
     * This is the configuration object Grunt uses to give each plugin its
     * instructions.
     */
    var taskConfig = {
        /**
         * We read in our `package.json` file so we can access the package name and
         * version. It's already there, so we don't repeat ourselves here.
         */
        pkg: grunt.file.readJSON("package.json"),

        /**
         * The banner is the comment that is placed at the top of our compiled
         * source files. It is first processed as a Grunt template, where the `<%=`
         * pairs are evaluated based on this very configuration object.
         */
        meta: {
            banner:
                '/**\n' +
                ' * <%= pkg.name %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>\n' +
                ' * <%= pkg.homepage %>\n' +
                ' *\n' +
                ' * Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author %>\n' +
                ' * Licensed <%= pkg.license %>\n' +
                ' */\n'
        },

        /**
         * The directories to delete when `grunt clean` is executed.
         */
        clean: {
            build: {
                files: [{
                    src: [
                        '<%= build_dir %>',
                        '<%= compile_dir %>'
                    ]
                }]
            },
            coverage: {
                files: [{
                    dot: true,
                    src: ['./coverage']
                }]
            }
        },

        connect: {
            options: {
                port: 9000,
                hostname: 'localhost',
                livereload: 35729
            },
            coverage: {
                options: {
                    open: true,
                    keepalive: true,
                    base: './coverage/'
                }
            }
        },

        /**
         * The `copy` task just copies files from A to B. We use it here to copy
         * our project assets (images, fonts, etc.) and javascripts into
         * `build_dir`, and then to copy the assets to `compile_dir`.
         */
        copy: {
            build_assets: {
                files: [{
                    src: ['**'],
                    dest: '<%= build_dir %>/assets/',
                    cwd: 'src/assets',
                    expand: true
                }]
            },
            build_appjs: {
                files: [{
                    src: ['<%= app_files.js %>'],
                    dest: '<%= build_dir %>/',
                    cwd: '.',
                    expand: true
                }]
            },
            build_appmodules: {
                files: [{
                    src: ['<%= app_files.modules %>'],
                    dest: '<%= build_dir %>/modules',
                    cwd: '.',
                    expand: true
                }]
            },
            build_vendorjs: {
                files: [{
                    src: ['<%= vendor_files.js %>'],
                    dest: '<%= build_dir %>/',
                    cwd: '.',
                    expand: true
                }]
            },
            build_vendorassets: {
                files: [{
                    src: ['<%= vendor_files.assets %>'],
                    dest: '<%= build_dir %>/assets',
                    flatten: true,
                    cwd: '.',
                    expand: true
                }]
            },
            compile_assets: {
                files: [{
                    src: ['**'],
                    dest: '<%= compile_dir %>/assets',
                    cwd: '<%= build_dir %>/assets',
                    expand: true
                }]
            }
        },

        /**
         * `grunt concat` concatenates multiple source files into a single file.
         */
        concat: {
            /**
             * The `compile_js` target is the concatenation of our application source
             * code and all specified vendor source code into a single file.
             */
            compile_js: {
                options: {
                    banner: '<%= meta.banner %>'
                },
                src: [
                    '<%= vendor_files.js %>',
                    'module.prefix',
                    '<%= build_dir %>/modules/**/*.js',
                    '<%= build_dir %>/src/**/*.js',
                    '<%= html2js.app.dest %>',
                    'module.suffix'
                ],
                dest: '<%= compile_dir %>/assets/<%= pkg.name %>.js'
            }
        },

        /**
         * `ng-annotate` annotates the sources before minifying. That is, it allows us
         * to code without the array syntax.
         */
        ngAnnotate: {
            compile: {
                files: [{
                    src: ['<%= app_files.js %>'],
                    cwd: '<%= build_dir %>',
                    dest: '<%= build_dir %>',
                    expand: true
                }]
            }
        },

        /**
         * Minify the sources!
         */
        uglify: {
            compile: {
                options: {
                    banner: '<%= meta.banner %>'
                },
                files: {
                    '<%= concat.compile_js.dest %>': '<%= concat.compile_js.dest %>'
                }
            }
        },

        sass: {
            dev: {
                files: {
                    '<%= build_dir %>/assets/<%= pkg.name %>.css': '<%= app_files.scss %>'
                },
                options: {
                    sourceMap     : true,
                    sourceMapEmbed: true,
                    outputStyle   : 'expanded'
                }
            },
            prod: {
                files: {
                    '<%= compile_dir %>/assets/<%= pkg.name %>.css': '<%= app_files.scss %>'
                },
                options: {
                    sourceMap  : false,
                    outputStyle: 'compressed'
                }
            }
        },

        /**
         * `jshint` defines the rules of our linter as well as which files we
         * should check. This file, all javascript sources, and all our unit tests
         * are linted based on the policies listed in `options`. But we can also
         * specify exclusionary patterns by prefixing them with an exclamation
         * point (!); this is useful when code comes from a third party but is
         * nonetheless inside `src/`.
         */
        jshint: {
            src: [
                '<%= app_files.js %>'
            ],
            test: [
                '<%= app_files.jsunit %>'
            ],
            gruntfile: [
                'Gruntfile.js'
            ],
            options: {
                curly: true,
                immed: true,
                newcap: true,
                noarg: true,
                sub: true,
                boss: true,
                eqnull: true
            }
        },

        /**
         * HTML2JS is a Grunt plugin that takes all of your template files and
         * places them into JavaScript files as strings that are added to
         * AngularJS's template cache. This means that the templates too become
         * part of the initial payload as one JavaScript file. Neat!
         */
        html2js: {
            /**
             * These are the templates from `src/app`.
             */
            app: {
                options: {
                    base: 'src/app'
                },
                src: ['<%= app_files.atpl %>'],
                dest: '<%= build_dir %>/templates-app.js'
            }
        },

        /**
         * The Karma configurations.
         */
        karma: {
            options: {
                configFile: '<%= build_dir %>/karma-unit.js'
            },
            unit: {
                runnerPort: 9101,
                background: true
            },
            coverage: {
                singleRun: true,
                reporters: ['dots', 'notify', 'coverage']
            },
            continuous: {
                singleRun: true,
                reporters: ['dots', 'junit'],
                junitReporter: {
                    outputFile: 'test-results.xml',
                    useBrowserName: false
                }
            }
        },

        /**
         * This task compiles the karma template so that changes to its file array
         * don't have to be managed manually.
         */
        karmaconfig: {
            unit: {
                dir: '<%= build_dir %>',
                src: [
                    'vendor/jquery/dist/jquery.js',
                    '<%= vendor_files.js %>',
                    '<%= html2js.app.dest %>',
                    'vendor/angular-mocks/angular-mocks.js',
                    'vendor/jasmine-promise-matchers/dist/jasmine-promise-matchers.js',
                    'vendor/jasmine-fixture/dist/jasmine-fixture.js',
                    '<%= app_files.modules %>',
                    '<%= app_files.js %>',
                    '<%= app_files.jsunit %>'
                ]
            }
        },

        /**
         * And for rapid development, we have a watch set up that checks to see if
         * any of the files listed below change, and then to execute the listed
         * tasks when they do. This just saves us from having to type "grunt" into
         * the command-line every time we want to see what we're working on; we can
         * instead just leave "grunt watch" running in a background terminal. Set it
         * and forget it, as Ron Popeil used to tell us.
         *
         * But we don't need the same thing to happen for all the files.
         */
        delta: {
            /**
             * By default, we want the Live Reload to work for all tasks; this is
             * overridden in some tasks (like this file) where browser resources are
             * unaffected. It runs by default on port 35729, which your browser
             * plugin should auto-detect.
             */
            options: {
                livereload: true
            },

            /**
             * When the Gruntfile changes, we just want to lint it. In fact, when
             * your Gruntfile changes, it will automatically be reloaded!
             */
            gruntfile: {
                files: 'Gruntfile.js',
                tasks: ['jshint:gruntfile'],
                options: {
                    livereload: false
                }
            },

            /**
             * When our JavaScript source files change, we want to run lint them and
             * run our unit tests.
             */
            jssrc: {
                files: [
                    '<%= app_files.js %>'
                ],
                tasks: ['jshint:src', 'nggettext_extract', 'karma:continuous', 'copy:build_appmodules', 'copy:build_appjs', 'copy:compile_assets', 'concat']
            },

            /**
             * When assets are changed, copy them. Note that this will *not* copy new
             * files, so this is probably not very useful.
             */
            assets: {
                files: [
                    'src/assets/**/*'
                ],
                tasks: ['copy:build_assets', 'copy:compile_assets']
            },

            /**
             * When our templates change, we only rewrite the template cache.
             */
            tpls: {
                files: [
                    '<%= app_files.atpl %>'
                ],
                tasks: ['nggettext_extract', 'html2js', 'concat']
            },

            /**
             * When the CSS files change, we need to compile and minify them.
             */
            sass: {
                files: ['src/**/*.scss'],
                tasks: ['sass:dev', 'copy:compile_assets']
            },

            /**
             * When a JavaScript unit test file changes, we only want to lint it and
             * run the unit tests. We don't want to do any live reloading.
             */
            jsunit: {
                files: [
                    '<%= app_files.jsunit %>'
                ],
                tasks: ['jshint:test', 'karma:continuous'],
                options: {
                    livereload: false
                }
            },

            po: {
                files: ['po/*.po'],
                tasks: ['nggettext_compile', 'copy:build_appmodules', 'concat']
            }
        },

        nggettext_extract: {
            pot: {
                options: {
                    lineNumbers: false
                },
                files: {
                    'po/template.pot': ['src/**/*.html', 'src/**/*.js']
                }
            }
        },

        nggettext_compile: {
            all: {
                files: {
                    '<%= build_dir %>/src/translations.js': ['po/*.po']
                }
            }
        }
    };

    grunt.initConfig(grunt.util._.extend(taskConfig, userConfig));

    /**
     * In order to make it safe to just compile or copy *only* what was changed,
     * we need to ensure we are starting from a clean, fresh build. So we rename
     * the `watch` task to `delta` (that's why the configuration var above is
     * `delta`) and then add a new task called `watch` that does a clean build
     * before watching for changes.
     */
    grunt.renameTask('watch', 'delta');
    grunt.registerTask('watch', [
        'prepare',
        'soft-compile',
        'karmaconfig',
        'delta'
    ]);

    /**
     * The `prepare` task gets your app ready to run for development and testing.
     */
    grunt.registerTask('prepare', [
        'clean:build',
        'nggettext_extract',
        'html2js',
        'copy:build_assets',
        'copy:build_appmodules',
        'copy:build_appjs',
        'copy:build_vendorjs',
        'copy:build_vendorassets'
    ]);

    /**
     * The `compile` task gets your app ready for deployment by concatenating and
     * minifying your code.
     */
    grunt.registerTask('compile', [
        'nggettext_compile',
        'sass:prod',
        'copy:compile_assets',
        'ngAnnotate',
        'concat',
        'uglify'
    ]);

    /**
     * Concatenates the code without minifying it (useful for dev)
     */
    grunt.registerTask('soft-compile', [
        'nggettext_compile',
        'sass:dev',
        'copy:compile_assets',
        'concat'
    ]);

    grunt.registerTask('build', 'Build and minify the app', function() {
        return grunt.task.run([
            'prepare',
            'compile'
        ]);
    });

    grunt.registerTask('default', ['build']);

    grunt.registerTask('test', 'Run unit tests and generate a junit report for the Continuous Integration', function() {
        return grunt.task.run([
            'jshint',
            'html2js',
            'karmaconfig',
            'karma:continuous'
        ]);
    });

    grunt.registerTask('coverage', 'Run unit tests and display test coverage results on your browser', function() {
        return grunt.task.run([
            'html2js',
            'karmaconfig',
            'clean:coverage',
            'karma:coverage',
            'connect:coverage'
        ]);
    });

    /**
     * A utility function to get all app JavaScript sources.
     */
    function filterForJS(files) {
        return files.filter(function(file) {
            return file.match(/\.js$/);
        });
    }

    /**
     * In order to avoid having to specify manually the files needed for karma to
     * run, we use grunt to manage the list for us. The `karma/*` files are
     * compiled as grunt templates for use by Karma. Yay!
     */
    grunt.registerMultiTask('karmaconfig', 'Process karma config templates', function() {
        var jsFiles = filterForJS(this.filesSrc);

        grunt.file.copy('karma/karma-unit.tpl.js', grunt.config('build_dir') + '/karma-unit.js', {
            process: function(contents, path) {
                return grunt.template.process(contents, {
                    data: {
                        scripts: jsFiles
                    }
                });
            }
        });
    });
};
