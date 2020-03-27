/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { dropdown as createDropdown } from "tlp";

export default function initCloneBarDropdown() {
    const button = document.getElementById("git-repository-clone-dropdown-button");
    const transport_buttons = document.getElementsByClassName("git-repository-clone-transport");
    const input = document.getElementById("git-repository-clone-input");
    const button_text = document.getElementById("git-repository-clone-button-text");
    const selected_icon = document.getElementById("git-repository-clone-selected-icon");

    const read_only_badge = document.getElementById("git-repository-clone-read-only");
    if (
        !button ||
        transport_buttons.length === 0 ||
        !input ||
        !button_text ||
        !selected_icon ||
        !read_only_badge
    ) {
        return;
    }

    const dropdown = createDropdown(button);

    input.addEventListener("click", function () {
        this.select();
    });

    for (const transport_button of transport_buttons) {
        transport_button.addEventListener("click", function () {
            const { url, isReadOnly } = this.dataset;
            input.value = url;
            button_text.textContent = this.text;
            this.insertBefore(selected_icon, this.firstChild);
            read_only_badge.classList.toggle("git-repository-clone-hidden", !isReadOnly);

            dropdown.hide();
        });
    }
}
