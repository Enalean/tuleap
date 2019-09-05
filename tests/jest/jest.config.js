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
        "^.+\\.js$": path.resolve(__dirname, "./babel-jest-process.js")
    },
    moduleNameMapper: {
        "tlp-fetch-mocks-helper-jest": path.resolve(
            __dirname,
            "../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper.js"
        ),
        tlp: path.resolve(__dirname, "../../src/www/themes/common/tlp/src/js/index.js"),
        "@tuleap-vue-components/(.*)$": path.resolve(
            __dirname,
            "../../src/www/scripts/vue-components/$1"
        )
    },
    setupFilesAfterEnv: [path.resolve(__dirname, "./fail-console-error-warning.js")],
    globals: {
        "vue-jest": {
            babelConfig: path.resolve(__dirname, "./babel.config.js")
        },
        "ts-jest": {
            tsConfig: path.resolve(__dirname, "./../../tsconfig.json")
        }
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
        "!**/karma*js",
        "!**/gulpfile.js",
        "!**/jest.config.js",
        "!**/*.d.ts",
        "!**/bootstrap/**",
        "!**/datepicker/**",
        "!**/FlamingParrot/keymaster-sequence/**",
        "!**/jquery/**",
        "!**/jscrollpane/**",
        "!**/jstimezonedetect/**",
        "!**/lightwindow/**",
        "!**/lytebox/**",
        "!**/protocheck/**",
        "!**/prototype/**",
        "!**/scriptaculous/**",
        "!**/select2/**",
        "!**/tablekit/**",
        "!**/textboxlist/**",
        "!**/viewportchecker/**",
        "!**/phpwiki/**/*.js",
        "!**/plugins/mediawiki/www/skins/common/ajax.js",
        "!**/src/www/api/explorer/**"
    ],
    resetModules: true,
    restoreMocks: true
};
