/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", () => {
    showDiffDirectlyIfInUrl();

    const toggle_diff_buttons = document.getElementsByClassName("toggle-diff");

    for (const diff_button of toggle_diff_buttons) {
        diff_button.addEventListener("click", function (event: Event) {
            event.preventDefault();
            toggleIcon(diff_button);
            toggleDiffContent(diff_button);
        });
    }
});

function toggleDiffContent(diff_button: Element): void {
    const diff = diff_button.nextElementSibling;
    if (diff) {
        diff.classList.toggle("follow-up-diff");
    }
}

export function toggleIcon(diff_button: Element): void {
    const right_icon_list = diff_button.getElementsByClassName("fa-caret-right");
    const left_icon_list = diff_button.getElementsByClassName("fa-caret-down");
    const right_icon = right_icon_list[0];
    const left_icon = left_icon_list[0];
    if (right_icon) {
        right_icon.classList.remove("fa-caret-right");
        right_icon.classList.add("fa-caret-down");
    } else if (left_icon) {
        left_icon.classList.remove("fa-caret-down");
        left_icon.classList.add("fa-caret-right");
    }
}

function showDiffDirectlyIfInUrl(): void {
    const url = document.location.toString(),
        reg_ex = /#followup_(\d+)/,
        matches = url.match(reg_ex);

    if (!matches) {
        return;
    }

    const followup_id = matches[1];

    const follow_up = document.getElementById("followup_" + followup_id);
    if (!follow_up) {
        throw Error("Follow up " + followup_id + " not foud in DOM");
    }

    const toggle_diff_button = follow_up.getElementsByClassName("toggle-diff");

    if (!toggle_diff_button[0]) {
        throw Error("Changeset " + followup_id + "does not have a diff button");
    }
    toggleIcon(toggle_diff_button[0]);
    toggleDiffContent(toggle_diff_button[0]);
}
