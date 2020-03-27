/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

!(function ($) {
    $(function () {
        var form = $("#siteadmin-access-anonymous");
        if (form.length === 0) {
            return;
        }

        var current_access_mode = form.attr("data-current-access-mode"),
            nb_restricted_users = form.attr("data-nb-restricted-users");

        form.find("[name=access_mode]").click(function () {
            enableSubmitButton();

            if (current_access_mode === "restricted" && nb_restricted_users > 0) {
                if ($(this).val() !== current_access_mode) {
                    $("#siteadmin-access-submit-panel-message").addClass("tlp-alert-warning");
                } else {
                    $("#siteadmin-access-submit-panel-message").removeClass("tlp-alert-warning");
                }
            }

            if ($(this).val() === "restricted") {
                $("#siteadmin-access-customize-ugroup-labels").show();
            } else {
                $("#siteadmin-access-customize-ugroup-labels").hide();
            }
        });

        form.find("[type=text]").keydown(function () {
            enableSubmitButton();
        });

        if (current_access_mode === "restricted") {
            $("#siteadmin-access-customize-ugroup-labels").show();
        }

        $("#toggle-anonymous-can-see-site-homepage").change(function () {
            $(this)[0].form.submit();
        });

        $("#toggle-anonymous-can-see-contact").change(function () {
            $(this)[0].form.submit();
        });

        function enableSubmitButton() {
            form.find("[type=submit]").prop("disabled", false);
        }
    });
})(window.jQuery);
