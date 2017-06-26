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
    var karmaTplPath = './karma-unit.tpl.js';

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
            banner: '/**\n' +
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
            build_vendorcss: {
                files: [{
                    src: ['<%= vendor_files.css %>'],
                    dest: '<%= build_dir %>/assets/',
                    cwd: '.',
                    expand: true,
                    flatten: true
                }]
            },
            build_vendorassets: {
                files: [{
                    src: ['<%= vendor_files.assets %>'],
                    dest: '<%= build_dir %>/',
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
            },
            css_to_scss: {
                files: [
                    {
                        expand: true,
                        cwd: '<%=  vendor_dir %>',
                        src: ['**/*.css'],
                        dest: '<%=  vendor_dir %>',
                        filter: 'isFile',
                        ext: ".scss"
                    }
                ]
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
                    'module.prefix',
                    '<%= build_dir %>/modules/**/*.js',
                    '<%= build_dir %>/src/**/*.js',
                    '<%= html2js.app.dest %>',
                    'module.suffix'
                ],
                dest: '<%= compile_dir %>//<%= pkg.name %>.js'
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
            prod: {
                files: {
                    '<%= compile_dir %>/assets/<%= pkg.name %>.css': '<%= app_files.scss %>'
                },
                options: {
                    sourcemap: 'none',
                    style: 'compressed'
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
                    base: 'src',
                    module: 'tuleap-artifact-modal-templates'
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
                    '<%= vendor_files.js %>',
                    '<%= html2js.app.dest %>',
                    'vendor/angular-mocks/angular-mocks.js',
                    'vendor/jasmine-promise-matchers/dist/jasmine-promise-matchers.js',
                    '<%= app_files.modules %>',
                    '<%= app_files.js %>',
                    '<%= app_files.jsunit %>'
                ]
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

    grunt.registerTask('default', ['build']);

    /**
     * The `build` task gets your app ready to run for development and testing.
     */
    grunt.registerTask('build', [
        'clean:build',
        'nggettext_extract',
        'html2js',
        'copy:css_to_scss',
        'copy:build_assets',
        'copy:build_appmodules',
        'copy:build_appjs',
        'copy:build_vendorjs',
        'nggettext_compile',
        'sass:prod',
        'copy:compile_assets',
        'ngAnnotate',
        'concat',
        'uglify'
    ]);

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

        grunt.file.copy(karmaTplPath, grunt.config('build_dir') + '/karma-unit.js', {
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
