/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

/* global module:readonly */

var escaper = {
    entityMap: {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
        "/": "&#x2F;",
    },
    html: function (text) {
        return String(text).replace(/[&<>"'/]/g, function fromEntityMap(s) {
            return escaper.entityMap[s];
        });
    },
};

if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = {
        escaper: escaper,
    };
} else {
    var tuleap = window.tuleap || {};
    tuleap.escaper = escaper;
}
