/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

/**
 * I make sure that the sidebar and the main content are not hidden under the
 * top navbar whent the motd is displayed
 */
import $ from "jquery";

$(document).ready(function () {
    $(".navbar > .motd").each(updateTopMarginAccordinglyToMOTDHeight);
});

function updateTopMarginAccordinglyToMOTDHeight() {
    var motd_element = this,
        main_content_element = $(motd_element).parents(".wrapper").find(".main"),
        initial_margin_top = parseInt($(main_content_element).css("margin-top"), 10);

    function motdResized() {
        var height_of_motd = $(motd_element).outerHeight();
        main_content_element.css("margin-top", initial_margin_top + height_of_motd + "px");
    }

    $(window).resize(motdResized);
    motdResized();
}
