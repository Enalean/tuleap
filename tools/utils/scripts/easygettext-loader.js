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

const { formatPOFileToVueGettext } = require("@tuleap/vue2-gettext-init");
const Pofile = require("pofile");

function po2json(poContent) {
    const catalog = Pofile.parse(poContent);
    if (!catalog.headers.Language) {
        throw new Error("No Language headers found!");
    }
    return {
        headers: catalog.headers,
        messages: formatPOFileToVueGettext(catalog.items),
    };
}

/**
 * "Compiles" po files to easygettext json format
 */
module.exports = function (content) {
    const json = po2json(content);
    return JSON.stringify(json);
};
