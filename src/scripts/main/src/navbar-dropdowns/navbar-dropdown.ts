/*
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

import { createPopover } from "@tuleap/tlp-popovers";

export function init(): void {
    const user_dropdown = document.getElementById("nav-dropdown-user");
    const user_content = document.getElementById("nav-dropdown-user-content");
    if (user_dropdown && user_content) {
        createPopover(user_dropdown, user_content, {
            trigger: "click",
            placement: "bottom-end",
        });
    }

    const new_dropdown = document.getElementById("nav-dropdown-new");
    const new_dropdown_content = document.getElementById("nav-dropdown-new-content");
    if (new_dropdown && new_dropdown_content) {
        createPopover(new_dropdown, new_dropdown_content, {
            trigger: "click",
            placement: "bottom-end",
        });
    }
}
