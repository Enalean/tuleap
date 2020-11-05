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

/*
 * For testing purpose only
 */

export function appendSimpleOptionsToSourceSelectBox(select: HTMLSelectElement): void {
    let i;
    for (i = 0; i < 3; i++) {
        const option = document.createElement("option");
        option.value = "value_" + i;
        option.innerText = "Value " + i;
        option.setAttribute("data-item-id", "list-picker-item-value_" + i);
        select.appendChild(option);
    }
    const option_with_label = document.createElement("option");
    option_with_label.value = "value_" + i;
    option_with_label.label = "Value " + i;
    option_with_label.setAttribute("data-item-id", "list-picker-item-value_" + i);
    select.appendChild(option_with_label);
}

export function appendGroupedOptionsToSourceSelectBox(select: HTMLSelectElement): void {
    let option_index = 0;
    ["Group 1", "Group 2"].forEach((group_name: string) => {
        const group = document.createElement("optgroup");
        group.setAttribute("label", group_name);

        for (let i = 0; i < 3; i++) {
            const option = document.createElement("option");
            option.value = "value_" + option_index;
            option.innerText = "Value " + option_index;
            option.setAttribute("data-item-id", "list-picker-item-value" + option_index);
            group.appendChild(option);

            if (option_index === 5) {
                option.setAttribute("disabled", "disabled");
            }

            option_index++;
        }

        select.appendChild(group);
    });
}
