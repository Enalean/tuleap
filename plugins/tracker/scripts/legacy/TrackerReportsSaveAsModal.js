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
    document.observe("dom:loaded", function () {
        if ($("#tracker_report_updater_saveas-modal") && $("#tracker_report_form")) {
            $("#tracker_report_updater_saveas-modal").on("hide", function () {
                $("#report_copy_name_input").val("");
            });
        }
    });
})(window.jQuery);
