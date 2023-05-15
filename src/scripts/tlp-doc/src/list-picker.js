/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { createListPicker } from "@tuleap/list-picker";

export function initSingleListPickers(example) {
    if (example.id !== "example-list-picker-") {
        return;
    }
    createListPicker(document.querySelector("#list-picker-sb"), {
        placeholder: "Choose a value",
        is_filterable: true,
    });

    createListPicker(document.querySelector("#list-picker-sb-with-optgroups"), {
        placeholder: "Choose a value",
        is_filterable: true,
    });

    createListPicker(document.querySelector("#list-picker-sb-disabled"), {
        placeholder: "You can't choose any value yet",
    });

    createListPicker(document.querySelector("#list-picker-sb-error"), {
        placeholder: "Choose a value",
    });

    createListPicker(document.querySelector("#list-picker-sb-avatars"), {
        placeholder: "Choose a GoT character",
        is_filterable: true,
        items_template_formatter: (html, value_id, option_label) => {
            if (value_id === "103" || value_id === "108") {
                return html`<i class="fa-solid fa-fw fa-user-slash"></i> ${option_label}`;
            }
            return html`<i class="fa-solid fa-fw fa-user"></i> ${option_label}`;
        },
    });
}

export function initMultipleListPickers(example) {
    if (example.id !== "example-multi-list-picker-") {
        return;
    }
    createListPicker(document.querySelector("#list-picker-msb"), {
        placeholder: "Choose some values in the list",
    });

    createListPicker(document.querySelector("#list-picker-msb-grouped"), {
        placeholder: "Choose some values in the list",
    });

    createListPicker(document.querySelector("#list-picker-msb-disabled"), {
        placeholder: "Choose some values in the list",
    });

    createListPicker(document.querySelector("#list-picker-msb-error"), {
        placeholder: "Choose some values in the list",
    });

    createListPicker(document.querySelector("#list-picker-msb-none"), {
        none_value: "100",
    });

    createListPicker(document.querySelector("#list-picker-msb-avatars"), {
        placeholder: "Choose GoT characters",
        items_template_formatter: (html, value_id, option_label) => {
            if (value_id === "103" || value_id === "108") {
                return html`<i class="fa-solid fa-fw fa-user-slash"></i> ${option_label}`;
            }
            return html`<i class="fa-solid fa-fw fa-user"></i> ${option_label}`;
        },
    });
}
