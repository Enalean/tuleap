/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { createDropdown } from "@tuleap/tlp-dropdown";

document.addEventListener("DOMContentLoaded", () => {
    for (const trigger of document.querySelectorAll(".forumml-thread-message-action")) {
        createDropdown(trigger);
    }

    for (const monospace_switch of document.querySelectorAll(
        ".forumml-thread-message-action-monospace"
    )) {
        if (!(monospace_switch instanceof HTMLElement)) {
            continue;
        }

        monospace_switch.addEventListener("change", () => {
            const body_id = monospace_switch.dataset?.target;
            if (!body_id) {
                return;
            }

            const body = document.getElementById(body_id);
            if (!body) {
                return;
            }

            body.classList.toggle("forumml-thread-message-body-monospace");
        });
    }
});
