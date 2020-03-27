/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

!(function ($) {
    $(document).ready(function () {
        $("#events_box").change(function () {
            if (this.value === "any") {
                hideSubEventsBox();
            } else {
                showSubEventsBox();
            }
        });
    });

    function showSubEventsBox() {
        $("#sub_events_box").css("display", "block");
    }

    function hideSubEventsBox() {
        $("#sub_events_box").css("display", "none");
    }
})(window.jQuery);
