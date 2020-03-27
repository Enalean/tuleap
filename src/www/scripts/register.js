/**
 * Copyright (c) Enalean SAS - 2014-2018. All rights reserved
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

/* global jQuery:readonly */

var tuleap = tuleap || {};

(function ($) {
    tuleap.register = {
        handleTooltip: function () {
            $("input, textarea").each(function () {
                var helper = $("#" + $(this).attr("id") + "-tooltip");
                if (helper.length > 0) {
                    $(this).popover({
                        html: true,
                        trigger: "focus",
                        container: "#register-background",
                        content: helper.html(),
                    });

                    $(this).on("shown.bs.popover", function () {
                        $(this).data("popover").$tip.find(".popover-content").html(helper.html());
                    });
                }
            });
        },

        handleDatepicker: function () {
            $(".datetimepicker").datetimepicker({
                pickTime: false,
            });
        },

        handleUsername: function () {
            $("#form_loginname").keyup(function () {
                if ($(this).val() !== $(this).val().toLowerCase()) {
                    $(this).val($(this).val().toLowerCase());
                }
            });
        },
    };

    $(document).ready(function () {
        tuleap.register.handleTooltip();
        tuleap.register.handleDatepicker();
        tuleap.register.handleUsername();
    });
})(jQuery);
