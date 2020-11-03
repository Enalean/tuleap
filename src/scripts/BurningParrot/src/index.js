/**
 * Copyright (c) 2018, Enalean. All rights reserved
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

import { init as initNavbarDropdown } from "./navbar-dropdown.js";
import { initSidebarPosition } from "../../tuleap/sidebar-position.ts";
import { initMainPosition } from "../../tuleap/main-position.ts";
import { initHeaderPosition } from "../../tuleap/header-position.ts";
import { init as initNavbarPinned } from "../../tuleap/navbar-pinned.js";
import { init as initSidebar } from "./sidebar.js";
import { init as initScrollbar } from "./scrollbar.js";
import { init as initProjectFlags } from "./project-flags.js";
import { init as initProjectPrivacy } from "./project-privacy.js";
import { initHelpDropdown } from "./help-dropdown";
import { init as initInviteBuddies } from "./invite-buddies";
import * as autocomplete from "../../tuleap/autocomplete-for-select2.js";

document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector(".sidebar");
    if (sidebar instanceof HTMLElement) {
        initSidebarPosition(sidebar);
    }
    const main = document.querySelector("main");
    if (main instanceof HTMLElement) {
        initMainPosition(main);
    }

    initHeaderPosition();
    initNavbarPinned();
    initNavbarDropdown();
    initSidebar();
    initHelpDropdown();
    initScrollbar();
    initProjectFlags();
    initProjectPrivacy();
    initInviteBuddies();
});

// tuleap.autocomplete* is still used by siteadmin scripts which may run without listening to DOMContentLoaded
const tuleap = window.tuleap || {};
Object.assign(tuleap, autocomplete);
window.tuleap = tuleap;
