/*
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/* eslint-disable */
var webpack_config = require('./webpack.config.js');
var path           = require('path');

// Karma configuration
module.exports = function(config) {
    config.set({

        // base path that will be used to resolve all patterns (eg. files, exclude)
        basePath: '.',

        // frameworks to use
        // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
        frameworks: ['jasmine'],

        // list of files / patterns to load in the browser
        files: [
            'tlp-mock.spec.js',
            'project-labeled-items/src/app.spec.js'
        ],

        // preprocess matching files before serving them to the browser
        // available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
        preprocessors: {
            'project-labeled-items/src/app.spec.js': ['webpack']
        },

        // web server port
        port: 9876,

        // enable / disable colors in the output (reporters and logs)
        colors: true,

        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_INFO,

        // enable / disable watching file and executing tests whenever any file changes
        autoWatch: false,

        // start these browsers
        // available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
        browsers: [process.platform !== 'linux' ? 'ChromeHeadless' : 'ChromiumHeadless'],

        webpack: webpack_config,

        webpackMiddleware: {
            stats: 'errors-only'
        }
    });

    if (process.env.NODE_ENV === 'test') {
        config.set({
            singleRun: true,
            reporters: ['dots', 'junit'],
            junitReporter: {
                outputDir     : process.env.REPORT_OUTPUT_FOLDER || '',
                outputFile    : 'test-results.xml',
                useBrowserName: false
            }
        });
    } else if (process.env.NODE_ENV === 'watch') {
        process.env.BABEL_ENV = 'test';
        config.set({
            reporters: ['dots'],
            autoWatch: true
        });
    } else if (process.env.NODE_ENV === 'coverage') {
        config.set({
            singleRun: true,
            reporters: ['dots', 'coverage'],
            coverageReporter: {
                dir      : path.resolve(__dirname, './coverage'),
                reporters: [
                        { type: 'html'}
                ]
            }
        })
    }
};
