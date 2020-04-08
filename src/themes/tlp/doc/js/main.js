/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

(function colorSwitcher() {
    var color_switchers = document.querySelectorAll(".color-switcher > a"),
        stylesheet = document.getElementById("tlp-stylesheet");

    [].forEach.call(color_switchers, function (color_switcher) {
        color_switcher.addEventListener("click", function () {
            if (!this.classList.contains("active")) {
                var color = this.classList[0].replace("switch-to-", "");
                var active_color_switcher = document.querySelector(".color-switcher > a.active");

                active_color_switcher.classList.remove("active");

                this.classList.add("active");

                document.body.classList.remove("orange", "blue", "green", "red", "grey", "purple");
                document.body.classList.add(color);

                loadStylesheet(color);
            }
        });
    });

    updateAllHexaColors();

    function loadStylesheet(color) {
        // Directly put in the global scope...
        var manifest = window.manifest_framework_file;
        var new_stylesheet = document.createElement("link");
        new_stylesheet.rel = "stylesheet";
        new_stylesheet.href = "/assets/core/" + manifest["tlp-" + color + ".css"];
        new_stylesheet.onload = function () {
            if (new_stylesheet.sheet.cssRules.length) {
                stylesheet.remove();
                stylesheet = new_stylesheet;
                updateAllHexaColors();
            }
        };
        document.head.insertBefore(new_stylesheet, stylesheet.nextSibling);
    }

    function updateAllHexaColors() {
        updateHexaColor("info");
        updateHexaColor("success");
        updateHexaColor("warning");
        updateHexaColor("danger");
        updateHexaColor("illustration-primary-color");
        updateHexaColor("illustration-secondary-light-color");
        updateHexaColor("illustration-secondary-dark-color");
        updateHexaColor("illustration-tertiary-color");
    }

    function updateHexaColor(name) {
        var element = document.querySelector(".doc-color-" + name);
        if (!element) {
            return;
        }

        var color = document.defaultView
            .getComputedStyle(element, null)
            .getPropertyValue("background-color");
        if (color.search("rgb") !== -1) {
            color = color.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            color = "#" + hex(color[1]) + hex(color[2]) + hex(color[3]);
        }
        document.querySelector(".doc-color-" + name + "-hexacode").innerText = color;
    }

    function hex(x) {
        return ("0" + parseInt(x, 10).toString(16)).slice(-2);
    }
})();

window.toggleMargins = function (id) {
    document.getElementById(id).classList.toggle("example-hide-margins");
};

var last_known_scroll_position = 0;
var ticking = false;

function showAtTopLink(scroll_pos) {
    var back_to_top = document.querySelector("#back-to-top");
    if (!back_to_top) {
        return;
    }

    if (scroll_pos > 600) {
        back_to_top.style.display = "flex";
    } else {
        back_to_top.style.display = "none";
    }
}

window.addEventListener("scroll", function () {
    last_known_scroll_position = window.scrollY;
    if (!ticking) {
        window.requestAnimationFrame(function () {
            showAtTopLink(last_known_scroll_position);
            ticking = false;
        });
    }
    ticking = true;
});
