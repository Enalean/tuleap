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
import { init as initProjectNavbarDropdown } from "./navbar-dropdown-projects.js";
import { init as initMotd } from "./motd.js";
import { init as initSidebar } from "./sidebar.js";
import { init as initScrollbar } from "./scrollbar.js";
import { init as initProjectFlags } from "./project-flags.js";
import { init as initProjectPrivacy } from "./project-privacy.js";
import { init as initNavbarHistory } from "../../navbar-history/index-burningparrot.js";
import * as autocomplete from "../../tuleap/autocomplete-for-select2.js";

document.addEventListener("DOMContentLoaded", () => {
    initNavbarDropdown();
    initProjectNavbarDropdown();
    initSidebar();
    initMotd();
    initScrollbar();
    initNavbarHistory();
    initProjectFlags();
    initProjectPrivacy();
});

// tuleap.autocomplete* is still used by siteadmin scripts which may run without listening to DOMContentLoaded
const tuleap = window.tuleap || {};
Object.assign(tuleap, autocomplete);
window.tuleap = tuleap;
