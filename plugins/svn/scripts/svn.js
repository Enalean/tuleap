/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import jQuery from "jquery";
import codendi from "codendi";

!(function ($) {
    function initAccessControlsVersionDisplayer() {
        var version_selected = $("#version-selected");
        var version_displayer = $("#other-version-content");
        var project_id = $("input[name=project_id]").val();
        var repo_id = $("input[name=repo_id]").val();

        function updateVersionDisplayer(response) {
            $("#old-access-file-form").css("visibility", "visible");

            if (response.content == "") {
                response.content = codendi.getText("plugin_svn", "empty_version");
                version_displayer.addClass("empty-version");
                version_displayer.attr("disabled", "");
            } else {
                version_displayer.removeClass("empty-version");
                version_displayer.removeAttr("disabled");
            }

            version_displayer.text(response.content);
        }

        version_selected.change(function () {
            if (this.value === "0") {
                version_displayer.text("");
                version_displayer.attr("disabled", "");
                $("#old-access-file-form").css("visibility", "hidden");
            } else {
                $.ajax({
                    url:
                        "/plugins/svn/?action=display-archived-version&accessfile_history_id=" +
                        this.value +
                        "&group_id=" +
                        project_id +
                        "&repo_id=" +
                        repo_id,
                    dataType: "json",
                }).success(updateVersionDisplayer);
            }
        });
    }

    function toggleSvnHelp() {
        $("#toggle-svn-help").click(function () {
            $("#svn-repository-help").toggle();
        });
    }

    $(document).ready(function () {
        initAccessControlsVersionDisplayer();
        toggleSvnHelp();
    });
})(jQuery);
