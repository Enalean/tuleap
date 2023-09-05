/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export function getNextItem(current_item: Element): Element | null {
    let next_item: Element | null = current_item;
    while (next_item) {
        next_item = next_item.nextElementSibling;
        if (next_item && next_item.classList.contains("list-picker-dropdown-option-value")) {
            break;
        }
    }

    if (next_item !== null) {
        return next_item;
    }

    const current_group = current_item.closest(".list-picker-item-group");
    if (current_group && current_group.nextElementSibling) {
        return current_group.nextElementSibling.querySelector(".list-picker-dropdown-option-value");
    }

    return null;
}

export function getPreviousItem(current_item: Element): Element | null {
    let previous_item: Element | null = current_item;
    while (previous_item) {
        previous_item = previous_item.previousElementSibling;
        if (
            previous_item &&
            previous_item.classList.contains("list-picker-dropdown-option-value")
        ) {
            break;
        }
    }

    if (previous_item !== null) {
        return previous_item;
    }

    const current_group = current_item.closest(".list-picker-item-group");
    if (current_group && current_group.previousElementSibling) {
        const next_group_items = current_group.previousElementSibling.querySelectorAll(
            ".list-picker-dropdown-option-value",
        );

        return next_group_items[next_group_items.length - 1] ?? null;
    }
    return null;
}
