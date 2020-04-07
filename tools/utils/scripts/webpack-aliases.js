/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
const path_to_tlp = path.join(__dirname, "../../../src/themes/tlp/");

const tlp_fetch_alias = {
    "tlp-fetch": path.join(path_to_tlp, "src/js/fetch-wrapper.js"),
};

const angular_tlp_alias = {
    "angular-tlp": path.join(path_to_tlp, "angular-tlp/index.js"),
};

const easygettext_loader_alias = {
    "easygettext-loader": path.resolve(__dirname, "./easygettext-loader.js"),
};

function extendAliases(...aliases) {
    return Object.assign({}, ...aliases);
}

module.exports = {
    extendAliases,
    angular_tlp_alias,
    easygettext_loader_alias,
    tlp_fetch_alias,
};
