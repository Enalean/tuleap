/**
 * Copyright (c) 2020-present, Enalean. All rights reserved
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

let motd: HTMLElement | null;
let main: HTMLElement | null;
let header: HTMLElement | null;
let sidebar: HTMLElement | null;

document.addEventListener("DOMContentLoaded", () => {
    motd = document.querySelector(".motd");
    main = document.querySelector(".main");
    header = document.querySelector("header");
    sidebar = document.querySelector(".sidebar-nav");
});

export function updateTopMarginAccordinglyToMOTDHeight(): void {
    if (!motd || !main || !header) {
        return;
    }

    const height_of_motd = motd.offsetHeight;
    const motd_padding_bottom = 60;

    main.style.marginTop = height_of_motd + "px";

    if (document.body.classList.contains("has-visible-project-banner")) {
        header.style.top = height_of_motd - motd_padding_bottom + "px";
    } else {
        header.style.top = height_of_motd + "px";
    }

    if (sidebar) {
        sidebar.style.marginTop = height_of_motd + "px";
    }
}
