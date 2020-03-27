/**
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

/**
 * I make sure that the sidebar and the main content are not hidden under the
 * top navbar whent the motd is displayed
 */

export { init };

function init() {
    const motd = document.querySelector(".header-motd");
    const main = document.querySelector("main");
    const sidebar = document.querySelector(".sidebar");

    if (motd) {
        updateTopMarginAccordinglyToMOTDHeight();
    }

    function updateTopMarginAccordinglyToMOTDHeight() {
        const initial_margin_top = {
            main: parseInt(getComputedStyle(main).marginTop, 10),
            sidebar: sidebar ? parseInt(getComputedStyle(sidebar).marginTop, 10) : false,
        };

        function motdResized() {
            const height_of_motd = motd.offsetHeight;
            main.style.marginTop = initial_margin_top.main + height_of_motd + "px";

            if (sidebar) {
                sidebar.style.marginTop = initial_margin_top.sidebar + height_of_motd + "px";
            }
        }

        window.addEventListener("resize", motdResized);
        motdResized();
    }
}
