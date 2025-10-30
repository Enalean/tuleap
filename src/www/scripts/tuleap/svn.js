/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

/* global codendi:readonly */
(function ($) {
    function initAccessControlsVersionDisplayer() {
        var version_selector = $("#old_access_file_container select");
        var version_displayer = $("#old_access_file_container textarea");
        var group_id = $("input[name=group_id]").val();

        function updateVersionDisplayer(response) {
            $("#old_access_file_form").css("visibility", "visible");

            if (response.content === null) {
                response.content = codendi.locales.svn_accessfile_history.empty;
                version_displayer.addClass("empty_version");
                version_displayer.attr("disabled", "");
            } else {
                version_displayer.removeClass("empty_version");
                version_displayer.removeAttr("disabled");
            }

            version_displayer.text(response.content);
        }

        version_selector.change(function () {
            if (this.value === "0") {
                version_displayer.text("");
                version_displayer.attr("disabled", "");
                $("#old_access_file_form").css("visibility", "hidden");
            } else {
                $.ajax({
                    url:
                        "/svn/admin/?func=access_control_version&accessfile_history_id=" +
                        this.value +
                        "&group_id=" +
                        group_id,
                    dataType: "json",
                }).success(updateVersionDisplayer);
            }
        });
    }

    $(document).ready(function () {
        initAccessControlsVersionDisplayer();
    });
})(window.jQuery);
