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
        var isFullScreen = false;
        var body = $("body");
        var main = $(".main");
        var info = $(".cardwall_board-milestone-info");
        var button = $("#go-to-fullscreen");

        function defineFullscreenClasses() {
            if (isFullScreen) {
                body.addClass("fullscreen");
                info.removeClass("mini");
            } else {
                body.removeClass("fullscreen");
                info.addClass("mini");
                main.removeAttr("style");
            }
        }

        /**
         * Comes from http://www.sitepoint.com/html5-full-screen-api/
         * Modified in December 2013 by the Enalean team
         *
         */
        function RunPrefixMethod(obj, method) {
            var prefix = ["webkit", "moz", "ms", "o", ""];
            var position = 0;
            var method_name;
            var type;

            while (position < prefix.length && !obj[method_name]) {
                method_name = method;
                if (prefix[position] == "") {
                    method_name = method_name.substr(0, 1).toLowerCase() + method_name.substr(1);
                }
                method_name = prefix[position] + method_name;
                type = typeof obj[method_name];
                if (type != "undefined") {
                    prefix = [prefix[position]];
                    return type == "function"
                        ? obj[method_name](Element.ALLOW_KEYBOARD_INPUT)
                        : obj[method_name];
                }
                position++;
            }
        }

        function defineMilestoneInfoBlockSize() {
            if (isFullScreen) {
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
            if (isFullScreen) {
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

        function exitFullScreen() {
            isFullScreen = false;

            RunPrefixMethod(document, "CancelFullScreen");
            scrollToTop();
            defineFullscreenClasses();
            defineMilestoneInfoBlockSize();
            updateButtonLabel();
        }

        function requestFullScreen() {
            isFullScreen = true;

            RunPrefixMethod(document.querySelector("html"), "RequestFullScreen");
            scrollToTop();
            defineFullscreenClasses();
            defineMilestoneInfoBlockSize();
            updateButtonLabel();
        }

        function browserIsIE() {
            //eslint-disable-next-line no-eval
            return eval("/*@cc_on !@*/false");
        }

        $(document).on("webkitfullscreenchange mozfullscreenchange fullscreenchange", function () {
            var fullscreenElement =
                document.fullscreenElement ||
                document.mozFullScreenElement ||
                document.webkitFullscreenElement;

            if (fullscreenElement == null) {
                exitFullScreen();
            }
        });

        if (browserIsIE()) {
            button.popover({
                trigger: "hover",
                placement: "left",
                html: true,
                title: codendi.locales.cardwall.no_fullscreen_title,
                content: codendi.locales.cardwall.no_fullscreen_content,
            });
        } else {
            button.on("click", function () {
                (function fullscreen() {
                    if (
                        RunPrefixMethod(document, "FullScreen") ||
                        RunPrefixMethod(document, "IsFullScreen")
                    ) {
                        exitFullScreen();
                    } else {
                        requestFullScreen();
                    }
                })();
            });
        }
    });
})(jQuery);
