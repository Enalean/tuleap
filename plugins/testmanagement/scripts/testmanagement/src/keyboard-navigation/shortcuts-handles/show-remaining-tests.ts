/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

export function showRemainingTests(doc: Document): void {
    const event = new InputEvent("change");

    const non_passed_filters = doc.querySelectorAll("[data-shortcut-filter-non-passed]");
    non_passed_filters.forEach((filter) => {
        if (filter instanceof HTMLInputElement && !filter.checked) {
            filter.checked = true;
            filter.dispatchEvent(event);
        }
    });

    const passed_filter = doc.querySelector("[data-shortcut-filter-passed]");
    if (passed_filter instanceof HTMLInputElement && passed_filter.checked) {
        passed_filter.checked = false;
        passed_filter.dispatchEvent(event);
    }
}
