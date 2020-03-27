/**
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

import jQuery from "jquery";

(function ($) {
    function confirmDeletionPopover() {
        $(".svn-notification-delete").each(function () {
            var id = $(this).data("popover-id");

            $(this)
                .popover({
                    container: $("#svn-admin-notifications-form"),
                    title: $("#" + id).data("title"),
                    content: $("#" + id).html(),
                })
                .addClass("popover-danger");
        });
    }

    function cannotSavePopover() {
        $(".svn-notification-save").each(function () {
            var id = $(this).data("popover-id");
            var popover_content = $("#" + id);

            $(this)
                .popover({
                    container: $("#svn-notification-cannot-save-popover-container"),
                    title: popover_content.data("title"),
                    content: popover_content.html(),
                })
                .addClass("popover-warning");
        });
    }

    function dismissPopover() {
        $(".svn-notification-delete").popover("hide");
        $(".svn-notification-save").popover("hide");
    }

    function bindShowPopover() {
        $(".svn-notification-delete").click(function (event) {
            event.preventDefault();

            dismissPopover();

            $(this).popover("show");
        });
    }

    $(function () {
        confirmDeletionPopover();
        cannotSavePopover();

        bindShowPopover();

        $("body").on("click", function (event) {
            if ($(event.target).hasClass("dismiss-popover")) {
                dismissPopover();
            }

            if (
                $(event.target).data("toggle") !== "popover" &&
                $(event.target).parents(".popover.in").length === 0 &&
                $(event.target).parents('[data-toggle="popover"]').length === 0
            ) {
                dismissPopover();
            }
        });
    });
})(jQuery);
