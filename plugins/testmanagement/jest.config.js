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

const base_config = require("../../tests/jest/jest.base.config.js");
const path = require("path");

module.exports = {
    ...base_config,
    displayName: "testmanagement",
    setupFiles: ["./scripts/testmanagement/tests/jest.setup.js"],
    moduleNameMapper: {
        ...base_config.moduleNameMapper,
        "^.+\\.html$": "identity-obj-proxy",
        "^angular$": "<rootDir>/node_modules/angular/index.js",
        "^@tuleap/ckeditor-image-upload$": path.resolve(
            __dirname,
            "./scripts/testmanagement/tests/ckeditor-image-upload-mock.js"
        ),
    },
};
