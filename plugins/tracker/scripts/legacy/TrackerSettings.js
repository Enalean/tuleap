/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

(function ($) {
    $(document).ready(function () {
        $(".tracker_color_selector").on("click", function () {
            var old_color = $("input[name=tracker_color]").val();
            var new_color = $(this).children(".fa-square").attr("data-color");

            $("i.fa-check.selected").removeClass("selected");
            $(this).children(".fa-check").addClass("selected");

            $("input[name=tracker_color]").val(new_color);
            $("span.tracker_color_preview .xref-in-title")
                .removeClass(old_color)
                .addClass(new_color);
        });
    });
})(window.jQuery);
