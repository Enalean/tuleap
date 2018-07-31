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
const path = require("path");

function getBaseConfig(config) {
    return {
        basePath: ".",
        plugins: [
            "karma-chrome-launcher",
            "karma-coverage",
            "karma-jasmine",
            "karma-junit-reporter",
            "karma-webpack"
        ],
        frameworks: ["jasmine"],
        client: {
            jasmine: {
                random: false
            }
        },
        port: 9876,
        colors: true,
        autoWatch: false,
        logLevel: config.LOG_INFO,
        browsers: [process.platform !== "linux" ? "ChromeHeadless" : "ChromiumHeadless"]
    };
}

const jasmine_promise_matchers_path = path.resolve(
    __dirname,
    "../../../node_modules/jasmine-promise-matchers/dist/jasmine-promise-matchers.js"
);

const jasmine_fixtures_path = path.resolve(
    __dirname,
    "../../../node_modules/jasmine-fixture/dist/jasmine-fixture.js"
);

module.exports = {
    getBaseConfig,
    jasmine_promise_matchers_path,
    jasmine_fixtures_path
};
