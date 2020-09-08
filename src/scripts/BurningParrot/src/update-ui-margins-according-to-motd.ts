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

// eslint-disable-next-line @typescript-eslint/consistent-type-assertions
const motd = document.querySelector(".motd") as HTMLElement;
const main = document.querySelector("main");
// eslint-disable-next-line @typescript-eslint/consistent-type-assertions
const sidebar = document.querySelector(".sidebar") as HTMLElement;
const header = document.querySelector("header");
const initial_margin_top = {
    main: main ? parseInt(getComputedStyle(main).marginTop, 10) : 0,
    sidebar: sidebar ? parseInt(getComputedStyle(sidebar).marginTop, 10) : 0,
};

export function updateTopMarginAccordinglyToMOTDHeight(): void {
    if (!motd || !main || !header) {
        return;
    }

    const height_of_motd = motd.offsetHeight;
    const motd_padding_bottom = 60;

    main.style.marginTop = initial_margin_top.main + height_of_motd + "px";

    if (document.body.classList.contains("has-visible-project-banner")) {
        header.style.top = height_of_motd - motd_padding_bottom + "px";
    } else {
        header.style.top = height_of_motd + "px";
    }

    if (sidebar) {
        sidebar.style.marginTop = initial_margin_top.sidebar + height_of_motd + "px";
    }
}
