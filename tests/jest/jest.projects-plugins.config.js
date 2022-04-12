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

const { jest_base_config } = require("@tuleap/build-system-configurator");

module.exports = {
    rootDir: path.resolve(__dirname, "../../"),
    projects: [
        "<rootDir>/plugins/*/jest.config.js",
        "<rootDir>/plugins/*/scripts/*/jest.config.js",
    ],
    collectCoverageFrom: [...jest_base_config.collectCoverageFrom],
};
