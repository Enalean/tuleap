/**
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
import { ListPickerItem } from "../type";

export function generateItemMapBasedOnSourceSelectOptions(
    source_select_box: HTMLSelectElement
): Map<string, ListPickerItem> {
    const map = new Map();
    let i = 0;
    for (const option of source_select_box.options) {
        if (option.value === "") {
            continue;
        }

        let group_id;
        if (option.parentElement && option.parentElement.nodeName === "OPTGROUP") {
            const label = option.parentElement.getAttribute("label");

            if (label !== null) {
                group_id = label.replace(" ", "").toLowerCase();
            }
        }

        const id = `item-${i}`;
        const item: ListPickerItem = {
            id,
            group_id,
            template: option.innerText,
            is_disabled: Boolean(option.hasAttribute("disabled")),
        };
        map.set(id, item);
        option.setAttribute("data-item-id", id);
        i++;
    }
    return map;
}
