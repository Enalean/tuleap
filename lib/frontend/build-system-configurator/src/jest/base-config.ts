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

import path from "path";

const esModules = [
    "d3-selection",
    "d3-array",
    "d3-shape",
    "d3-force",
    "d3",
    "lit",
    "internmap",
    "hybrids",
].join("|");

let coverage_directory = undefined;
if (process.env.COVERAGE_BASE_OUTPUT_DIR) {
    coverage_directory = process.env.COVERAGE_BASE_OUTPUT_DIR + "/" + path.basename(process.cwd());
}

const is_typechecking_enabled = process.env.DISABLE_TS_TYPECHECK !== "true";

export const base_config = {
    testEnvironment: "jsdom",
    transform: {
        "^.+\\.vue$": "@vue/vue2-jest",
        "^.+\\.ts$": "ts-jest",
        "^.+\\.js$": path.resolve(__dirname, "./babel-jest-process.js"),
    },
    moduleNameMapper: {
        "^.+\\.po$": "identity-obj-proxy",
        "^tlp$": path.resolve(__dirname, "../../../../../src/themes/tlp/src/js/index.ts"),
        "^@tuleap/tlp$": path.resolve(__dirname, "../../../../../src/themes/tlp/src/js/index.ts"),
        // alias to the source TS file to avoid running into "regeneratorRuntime" not defined errors in tests
        "^@tuleap/tlp-fetch$": path.resolve(__dirname, "../../../tlp-fetch/src/fetch-wrapper.ts"),
        "\\.(css|scss)(\\?inline)?$": "identity-obj-proxy",
    },
    setupFiles: [path.resolve(__dirname, "./fail-unhandled-promise-rejection.js")],
    setupFilesAfterEnv: [path.resolve(__dirname, "./fail-console-error-warning.js")],
    globals: {
        "vue-jest": {
            transform: {
                "^js$": path.resolve(__dirname, "./babel-jest-process.js"),
            },
        },
        "ts-jest": {
            diagnostics: is_typechecking_enabled,
            isolatedModules: !is_typechecking_enabled,
        },
    },
    snapshotSerializers: ["jest-serializer-vue"],
    testMatch: ["**/?(*.)+(test).{js,ts}"],
    collectCoverageFrom: [
        "**/*.{js,ts,vue}",
        "!**/node_modules/**",
        "!**/vendor/**",
        "!**/assets/**",
        "!**/frontend-assets/**",
        "!**/dist/**",
        "!**/tests/**",
        "!**/coverage/**",
        "!**/webpack*js",
        "!**/vite.config.ts",
        "!**/jest.config.js",
        "!**/*.d.ts",
        "!**/scripts/lib/**",
    ],
    // Transpile ESModules because they are not supported by NodeJS (yet)
    // They are only used in some part of Tuleap but to avoid wasting more
    // developers time than needed we consider they are present everywhere
    transformIgnorePatterns: [
        `/(?!${esModules})/`,
        "/angular-locker/",
        "/dragular/",
        "/ckeditor4/",
    ],
    resetModules: true,
    restoreMocks: true,
    coverageDirectory: coverage_directory,
};
