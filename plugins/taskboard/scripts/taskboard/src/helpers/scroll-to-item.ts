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
const NAVBAR_HEIGHT_AND_HEADER_HEIGHT_IN_PX = 95;
const HEADER_HEIGHT_IN_PX = 50;
const SIDEBAR_EXPANDED_WIDTH_IN_PX = 250;
const SIDEBAR_COLLAPSED_WIDTH_IN_PX = 50;
const ITEM_MARGIN_IN_PX = 10;

export function scrollToItemIfNeeded(
    taskboard_item: Element,
    element_in_fullscreen: Element | null,
): void {
    if (element_in_fullscreen !== null) {
        scrollToItemInAFullscreenTaskboard(taskboard_item, element_in_fullscreen);
    } else {
        scrollToItemItem(taskboard_item);
    }
}

function scrollToItemItem(taskboard_item: Element): void {
    const { top: current_top, left: current_left } = taskboard_item.getBoundingClientRect();

    if (current_top < NAVBAR_HEIGHT_AND_HEADER_HEIGHT_IN_PX) {
        const new_top =
            window.pageYOffset +
            current_top -
            NAVBAR_HEIGHT_AND_HEADER_HEIGHT_IN_PX -
            ITEM_MARGIN_IN_PX;

        const base_offset = window.pageXOffset + current_left - ITEM_MARGIN_IN_PX;
        const new_left = document.body.classList.contains("sidebar-collapsed")
            ? base_offset - SIDEBAR_COLLAPSED_WIDTH_IN_PX
            : base_offset - SIDEBAR_EXPANDED_WIDTH_IN_PX;

        window.scrollTo({ top: new_top, left: new_left, behavior: "smooth" });
    }
}

function scrollToItemInAFullscreenTaskboard(
    taskboard_item: Element,
    element_in_fullscreen: Element,
): void {
    const current_top = taskboard_item.getBoundingClientRect().top;

    if (current_top < HEADER_HEIGHT_IN_PX) {
        const new_top =
            element_in_fullscreen.scrollTop + current_top - HEADER_HEIGHT_IN_PX - ITEM_MARGIN_IN_PX;

        element_in_fullscreen.scrollTo({ top: new_top, left: 0, behavior: "smooth" });
    }
}
