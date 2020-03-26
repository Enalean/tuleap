/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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
var api;
var throttleTimeout;

function getSidebarUserPreference() {
    if ($("body").hasClass("sidebar-collapsed")) {
        return width_collapsed;
    }

    return width_expanded;
}

function setSidebarUserPreference(new_width) {
    var state = new_width === width_expanded ? "sidebar-expanded" : "sidebar-collapsed";

    $.ajax({
        type: "POST",
        url: "/account/update-sidebar-preference.php",
        data: {
            user_preference_name: "sidebar_state",
            sidebar_state: state,
        },
    });

    $("body").removeClass("sidebar-expanded sidebar-collapsed").addClass(state);
}

function updateSidebarWidth(new_width, duration) {
    updateNavbarLogo(new_width);

    $(".sidebar-nav").animate(
        {
            width: new_width,
        },
        duration
    );
    $(".sidebar-nav li a").css({
        width: new_width,
    });
    $(".main").animate(
        {
            marginLeft: new_width,
        },
        duration
    );

    if ($(".content").css("position") === "absolute") {
        $(".content").css("padding-left", new_width);
    }

    function emitSidebarSizeUpdated() {
        $(".sidebar-nav").trigger("sidebarSizeUpdated", [new_width]);
    }
    window.setTimeout(emitSidebarSizeUpdated, 0);
}

function updateNavbarLogo(new_width) {
    var logo = document.querySelector("#navbar-logo > .logo");

    if (new_width === width_expanded) {
        logo.classList.remove("logo-collapsed");
    } else {
        logo.classList.add("logo-collapsed");
    }
}

function updateSidebarIcon(direction) {
    $(".sidebar-collapse")
        .removeClass("fa-chevron-left fa-chevron-right")
        .addClass("fa-chevron-" + direction);
}

function updateSidebarTitle(show_only_icon) {
    if (show_only_icon) {
        $(".project-title-container").css({
            visibility: "hidden",
        });
    } else {
        $(".project-title-container").css({
            visibility: "visible",
        });
    }
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
    var new_direction = "left";
    var show_only_icon = false;
    var new_size;

    if (current_size === width_expanded) {
        new_size = width_collapsed;
        new_direction = "right";
        show_only_icon = true;
    } else {
        new_size = width_expanded;
    }

    setSidebarUserPreference(new_size);

    updateSidebarTitle(show_only_icon);
    updateSidebarWidth(new_size, duration);
    updateSidebarIcon(new_direction, show_only_icon);
    updateSidebarServices(show_only_icon, duration);
    updateCustomScrollbar();
}

function updateCustomScrollbar() {
    api.destroy();
    throttleTimeout = null;
    initCustomScrollbar();
}

function initCustomScrollbar() {
    $(".sidebar-nav")
        .jScrollPane({
            verticalGutter: 0,
            hideFocus: true,
            contentWidth: getSidebarUserPreference(),
        })
        .bind("mousewheel", function (e) {
            e.preventDefault();
        });
    api = $(".sidebar-nav").data("jsp");

    $(window).bind("resize", function () {
        if (!throttleTimeout) {
            throttleTimeout = setTimeout(updateCustomScrollbar, 50);
        }
    });
}

$(window).load(function () {
    var current_size = getSidebarUserPreference();

    if ($(".sidebar-nav").length > 0) {
        initCustomScrollbar();

        $(".sidebar-nav li a").tooltip({
            placement: "right",
            container: "body",
        });

        if (current_size === null || current_size === width_expanded) {
            updateSidebarIcon("left");
            updateSidebarServices(false, 100);
        } else {
            updateSidebarIcon("right");
            updateSidebarServices(true, 100);
        }

        $(".sidebar-collapse").click(function () {
            sidebarCollapseEvent(100);
        });
    }
});
