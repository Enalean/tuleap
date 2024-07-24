/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

const markAsStickyIfNeeded = (list_item: Element): void => {
    const header = list_item.querySelector(".section-header");
    if (!header) {
        return;
    }

    const sticky_top = parseInt(window.getComputedStyle(header).top, 10);
    const current_top = header.getBoundingClientRect().top;

    list_item.setAttribute("data-is-sticking", String(current_top === sticky_top));
};

export const onScrollStickSectionNumbers = (sections_container: HTMLOListElement): void => {
    document.addEventListener(
        "scroll",
        (): void => {
            for (const list_item of sections_container.children) {
                markAsStickyIfNeeded(list_item);
            }
        },
        { passive: true },
    );
};
