/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

export function setButtonToDisabledWithSpinner(button: HTMLButtonElement): void {
    button.disabled = true;
    const icon_node = button.firstElementChild;
    if (!icon_node || icon_node.tagName !== "I") {
        throw new Error("Icon on button to add team does not exist");
    }

    icon_node.classList.remove("fas", "fa-plus");
    icon_node.classList.add("fa", "fa-spin", "fa-circle-o-notch");
}

export function resetButtonToAddTeam(button: HTMLButtonElement): void {
    button.disabled = false;
    const icon_node = button.firstElementChild;
    if (!icon_node) {
        throw new Error("Icon on button to add team does not exist");
    }

    icon_node.classList.remove("fa", "fa-spin", "fa-circle-o-notch");
    icon_node.classList.add("fas", "fa-plus");
}
