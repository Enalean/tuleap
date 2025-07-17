/**
 * Copyright (c) Enalean, 2013-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/* global codendi:readonly jQuery:readonly */

/**
 * This script manage the fullscreen mode of the cardwall
 */

(function ($) {
    $(document).ready(function () {
        function isCurrentlyInFullscreen() {
            return document.fullscreenElement !== null;
        }

        var body = $("body");
        var main = $(".main");
        var info = $(".cardwall_board-milestone-info");
        var button = $("#go-to-fullscreen");

        function defineFullscreenClasses() {
            if (isCurrentlyInFullscreen()) {
                body.addClass("fullscreen");
                info.removeClass("mini");
            } else {
                body.removeClass("fullscreen");
                info.addClass("mini");
                main.removeAttr("style");
            }
        }

        function defineMilestoneInfoBlockSize() {
            if (isCurrentlyInFullscreen()) {
                main.css("margin-left", 0);
                $(".milestone-name").addClass("span3");
                $(".milestone-days").removeClass("span5").addClass("span3");
                $(".milestone-capacity").removeClass("span5").addClass("span4");
            } else {
                $(".milestone-name").removeClass("span3");
                $(".milestone-days").removeClass("span3").addClass("span5");
                $(".milestone-capacity").removeClass("span4").addClass("span5");
            }
        }

        function updateButtonLabel() {
            if (isCurrentlyInFullscreen()) {
                button.html(
                    '<i class="fa fa-compress"></i>' + codendi.locales.cardwall.exit_fullscreen,
                );
            } else {
                button.html(
                    '<i class="fa fa-desktop"></i>' + codendi.locales.cardwall.go_to_fullscreen,
                );
            }
        }

        function scrollToTop() {
            $("html, body").scrollTop(0);
        }

        document.addEventListener("fullscreenchange", function () {
            scrollToTop();
            defineFullscreenClasses();
            defineMilestoneInfoBlockSize();
            updateButtonLabel();
        });

        button.on("click", function () {
            if (isCurrentlyInFullscreen()) {
                document.exitFullscreen();
            } else {
                document.querySelector("html").requestFullscreen();
            }
        });
    });
})(jQuery);
