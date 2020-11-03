/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import "core-js/stable";
import "regenerator-runtime/runtime";
import { initSidebarPosition } from "../tuleap/sidebar-position.ts";
import { initMainPosition } from "../tuleap/main-position.ts";
import { initHeaderPosition } from "../tuleap/header-position.ts";
import { init as initNavbarPinned } from "../tuleap/navbar-pinned.js";
import { init as initInviteBuddies } from "./invite-buddies";

import "./sidebar.js";
import "./project-flags.js";
import "./project-privacy.js";
import "./help-dropdown.ts";

document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector(".sidebar-nav");
    if (sidebar instanceof HTMLElement) {
        initSidebarPosition(sidebar);
    }
    const main = document.querySelector(".main");
    if (main instanceof HTMLElement) {
        initMainPosition(main);
    }

    initHeaderPosition();
    initNavbarPinned();
    initInviteBuddies();
});
