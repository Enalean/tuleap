/*
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

import * as d3 from "d3";
import tuleap from "tuleap";
import jQuery from "jquery";
import "./display-angular-feedback.js";

/*
 * Requires jQuery and d3.js
 */
(function ($) {
    $(document).ready(function () {
        $(".data-burndown-json").each(function () {
            var json = JSON.parse($(this).html()),
                placeholder = $(this).attr("data-for"),
                burndown;

            burndown = new tuleap.agiledashboard.Burndown(d3, json, {
                width: 310,
                height: 140,
            });
            burndown.display(d3.select("#" + placeholder));
        });

        var height = $("#home").css("height");
        $(".kanban-block").css("min-height", height);

        $("#add_kanban_button").tooltip();
    });
})(jQuery);
