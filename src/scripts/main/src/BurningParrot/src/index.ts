/**
 * Copyright (c) Enalean 2018 - Present. All rights reserved
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

import { init as initNavbarDropdown } from "../../navbar-dropdowns/navbar-dropdown";
import { initSidebarPosition } from "../../tuleap/sidebar-position";
import { initMainPosition } from "../../tuleap/main-position";
import { initHeaderPosition } from "../../tuleap/header-position";
import { init as initProjectPrivacy } from "./project-privacy";
import { init as initNavbarPinned } from "../../tuleap/navbar-pinned";
import { init as initSidebar } from "./sidebar";
import { initHelpDropdown } from "./help-dropdown";
import { init as initInviteBuddies } from "./invite-buddies";
import "../../global-shortcuts/index";

declare global {
    interface Window {
        tuleap: unknown;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    initSidebarPosition();
    const main = document.querySelector("main");
    if (main instanceof HTMLElement) {
        initMainPosition(main);
    }

    initHeaderPosition();
    initNavbarPinned();
    initNavbarDropdown();
    initSidebar();
    initHelpDropdown();
    initProjectPrivacy();
    initInviteBuddies();
});

const tuleap = window.tuleap || {};
window.tuleap = tuleap;
