/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

import $ from "jquery";

var width_collapsed = "50px";
var width_expanded = "250px";

function getSidebarUserPreference() {
    if ($("body").hasClass("sidebar-collapsed")) {
        return width_collapsed;
    }

    return width_expanded;
}

function setSidebarUserPreference(new_width) {
    var state = new_width === width_expanded ? "sidebar-expanded" : "sidebar-collapsed";

    $.ajax({
        type: "PATCH",
        url: "/api/users/self/preferences",
        data: {
            key: "sidebar_state",
            value: state,
        },
    });

    $("body").removeClass("sidebar-expanded sidebar-collapsed").addClass(state);
}

function updateSidebarServices(show_only_icon) {
    if (show_only_icon) {
        $(".sidebar-nav li a > span").hide();
        $(".sidebar-nav li a").tooltip("enable");
    } else {
        $(".sidebar-nav li a > span").show();
        $(".sidebar-nav li a").tooltip("disable");
    }
}

function sidebarCollapseEvent(duration) {
    var current_size = getSidebarUserPreference();
    var show_only_icon = false;
    var new_size;

    if (current_size === width_expanded) {
        new_size = width_collapsed;
        show_only_icon = true;
    } else {
        new_size = width_expanded;
    }

    setSidebarUserPreference(new_size);
    updateSidebarServices(show_only_icon, duration);
}

$(window).load(function () {
    var current_size = getSidebarUserPreference();

    if ($(".sidebar-nav").length > 0) {
        $(".sidebar-nav li a").tooltip({
            placement: "right",
            container: "body",
        });

        if (current_size === null || current_size === width_expanded) {
            updateSidebarServices(false, 100);
        } else {
            updateSidebarServices(true, 100);
        }

        $("[data-sidebar-collapser]").click(function () {
            sidebarCollapseEvent(100);
        });
    }
});
