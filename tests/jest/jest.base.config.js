/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

module.exports = {
    transform: {
        "^.+\\.vue$": "vue-jest",
        "^.+\\.ts$": "ts-jest",
        "^.+\\.js$": path.resolve(__dirname, "./babel-jest-process.js"),
    },
    moduleNameMapper: {
        "^.+\\.po$": "identity-obj-proxy",
        "^tlp$": path.resolve(__dirname, "../../src/www/themes/common/tlp/src/js/index.js"),
        "^tlp-fetch$": path.resolve(
            __dirname,
            "../../src/www/themes/common/tlp/src/js/fetch-wrapper.js"
        ),
    },
    setupFiles: [path.resolve(__dirname, "./fail-unhandled-promise-rejection.js")],
    setupFilesAfterEnv: [path.resolve(__dirname, "./fail-console-error-warning.js")],
    globals: {
        "vue-jest": {
            babelConfig: path.resolve(__dirname, "./babel.config.js"),
        },
    },
    snapshotSerializers: ["jest-serializer-vue"],
    testMatch: ["**/?(*.)+(test).{js,ts}"],
    collectCoverageFrom: [
        "**/*.{js,ts,vue}",
        "!**/node_modules/**",
        "!**/vendor/**",
        "!**/assets/**",
        "!**/dist/**",
        "!**/tests/**",
        "!**/coverage/**",
        "!**/webpack*js",
        "!**/gulpfile.js",
        "!**/jest.config.js",
        "!**/*.d.ts",
    ],
    resetModules: true,
    restoreMocks: true,
};
