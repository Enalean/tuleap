/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
const karma_config = require("./karma-common-config.js");

function setupBaseKarmaConfig(config, webpack_config, coverage_path) {
    return Object.assign(
        karma_config.getBaseConfig(config),
        configureWebpackConfig(webpack_config),
        configureKarmaServer(coverage_path)
    );
}

function configureWebpackConfig(webpack_config) {
    return {
        webpack: webpack_config,
        webpackMiddleware: {
            stats: "errors-only"
        }
    };
}

function configureKarmaForSingleTest() {
    return {
        singleRun: true,
        reporters: ["dots", "junit"],
        junitReporter: {
            outputDir: process.env.REPORT_OUTPUT_FOLDER || "",
            outputFile: "test-results.xml",
            useBrowserName: false
        }
    };
}

function configureKarmaForWatchMode() {
    return {
        reporters: ["dots"],
        autoWatch: true
    };
}

function configureKarmaCoverage(coverage_directory) {
    return {
        singleRun: true,
        reporters: ["dots", "coverage"],
        coverageReporter: {
            dir: coverage_directory,
            reporters: [{ type: "html" }]
        }
    };
}

function configureKarmaServer(coverage_directory) {
    if (process.env.NODE_ENV === "test") {
        return configureKarmaForSingleTest();
    } else if (process.env.NODE_ENV === "watch") {
        process.env.BABEL_ENV = "test";
        return configureKarmaForWatchMode();
    } else if (process.env.NODE_ENV === "coverage") {
        return configureKarmaCoverage(coverage_directory);
    }
}

const configurator = {
    setupBaseKarmaConfig
};

Object.assign(configurator, karma_config);

module.exports = configurator;
