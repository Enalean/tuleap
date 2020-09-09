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

import { render } from "mustache";
import { sanitize } from "dompurify";

interface ListPickerOptions {
    placeholder?: string;
}

export function createListPicker(
    source_select_box: HTMLSelectElement | null,
    options?: ListPickerOptions
): void {
    if (source_select_box === null) {
        return;
    }

    const is_disabled = Boolean(source_select_box.disabled);
    const rendered_list_picker = getRenderedListPicker();

    hideSourceSelectBox(source_select_box);

    source_select_box.insertAdjacentHTML("afterend", sanitize(rendered_list_picker));

    function getRenderedListPicker(): string {
        return render(
            `
            <span class="list-picker{{# is_disabled }} list-picker-disabled {{/ is_disabled }}">
                <span class="list-picker-container list-picker-single" role="textbox" aria-readonly="true">
                   <span class="list-picker-placeholder">{{ placeholder }}</span>
                </span>
            </span>
            `,
            {
                placeholder: options?.placeholder,
                is_disabled,
            }
        );
    }

    function hideSourceSelectBox(source_select_box: HTMLSelectElement): void {
        source_select_box.classList.add("list-picker-hidden-accessible");
        source_select_box.setAttribute("tabindex", "-1");
        source_select_box.setAttribute("aria-hidden", "true");
    }
}
