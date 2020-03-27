/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

/* eslint-disable no-undef */

(function ($) {
    function confirmDeletionPopover() {
        $(".remove-tracker-webhook").each(function () {
            var id = $(this).data("popover-id");

            $(this).popover({
                container: "#tracker-webhooks",
                title: codendi.getText("tracker_webhooks", "title"),
                content: $("#" + id).html(),
            });
        });
    }

    function dismissPopover() {
        $(".remove-tracker-webhook").popover("hide");
    }

    function bindShowPopover() {
        $(".remove-tracker-webhook").click(function (event) {
            event.preventDefault();

            dismissPopover();

            $(this).popover("show");
        });
    }

    $(function () {
        $(".only-one-webhook").tooltip();

        confirmDeletionPopover();

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
