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

export function resetErrorOnSelectField(selector: HTMLSelectElement): void {
    const parent = selector.parentElement;
    if (!parent) {
        throw new Error("Parent of selector does not exist");
    }
    parent.classList.remove("tlp-form-element-error");
    const last_child = parent.lastElementChild;
    if (!last_child || last_child.tagName !== "P") {
        return;
    }
    last_child.remove();
}

export function setErrorMessageOnSelectField(selector: HTMLSelectElement, message: string): void {
    const parent = selector.parentElement;
    if (!parent) {
        throw new Error("Parent of selector does not exist");
    }
    parent.classList.add("tlp-form-element-error");

    const error_message_element = document.createElement("p");
    error_message_element.textContent = message;
    error_message_element.classList.add("tlp-text-danger");

    parent.appendChild(error_message_element);
}
