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
    const empty_option = document.createElement("option");
    empty_option.value = "";
    select.appendChild(empty_option);

    for (let i = 0; i < 3; i++) {
        const option = document.createElement("option");
        option.value = "value_" + i;
        option.innerText = "Value " + i;
        select.appendChild(option);
    }
}

export function appendGroupedOptionsToSourceSelectBox(select: HTMLSelectElement): void {
    const empty_option = document.createElement("option");
    empty_option.value = "";
    select.appendChild(empty_option);

    let option_index = 0;
    ["Group 1", "Group 2"].forEach((group_name: string) => {
        const group = document.createElement("optgroup");
        group.setAttribute("label", group_name);

        for (let i = 0; i < 3; i++) {
            const option = document.createElement("option");
            option.value = "value_" + option_index;
            option.innerText = "Value " + option_index;
            group.appendChild(option);

            if (option_index === 5) {
                option.setAttribute("disabled", "disabled");
            }

            option_index++;
        }

        select.appendChild(group);
    });
}
