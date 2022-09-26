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

const { defineJestConfiguration } = require("@tuleap/build-system-configurator");

const jest_base_config = defineJestConfiguration();
module.exports = {
    ...jest_base_config,
    displayName: "tuleap-core",
    collectCoverageFrom: [
        ...jest_base_config.collectCoverageFrom,
        "!common/**",
        "!www/**/jquery/**",
        "!www/**/lightwindow/**",
        "!www/**/lytebox/**",
        "!www/**/protocheck/**",
        "!www/**/prototype/**",
        "!www/**/scriptaculous/**",
        "!www/**/select2/**",
        "!www/**/tablekit/**",
        "!www/**/textboxlist/**",
        "!www/**/viewportchecker/**",
        "!www/**/bootstrap/**",
        "!www/**/datepicker/**",
        "!scripts/FlamingParrot/keymaster-sequence/**",
        "!scripts/switch-to/**",
    ],
    testPathIgnorePatterns: ["/node_modules/", "/themes/tlp/", "/scripts/switch-to/"],
    transform: {
        ...jest_base_config.transform,
        "^.+\\.vue$": "unplugin-vue2-script-setup/jest",
    },
};
