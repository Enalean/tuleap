/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import * as tlp from "tlp";

export default EnableTlpTableFilter;

EnableTlpTableFilter.$inject = ["$timeout"];

function EnableTlpTableFilter($timeout) {
    function preventSubmit(evt) {
        var event = evt || window.event;
        var key_code = event.charCode;

        if (event.keyCode) {
            key_code = event.keyCode;
        } else if (event.which) {
            key_code = event.which;
        }

        if (key_code === 13) {
            event.cancelBubble = true;
            event.returnValue = false;

            if (event.stopPropagation) {
                event.stopPropagation();
                event.preventDefault();
            }
        }
    }

    return {
        restrict: "A",
        link: function (scope, element) {
            $timeout(function () {
                var filterField = element[0];
                tlp.filterInlineTable(filterField);
                filterField.addEventListener("keydown", preventSubmit);
            });
        },
    };
}
