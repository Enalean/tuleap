/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { render, html } from "lit/html.js";

export type RemoveCurrentSelectionCallback = (event: Event) => void;

export const buildClearSelectionButtonElement = (
    remove_current_selection: RemoveCurrentSelectionCallback
): Element => {
    const document_fragment = document.createDocumentFragment();
    render(
        html`
            <span
                data-test="clear-current-selection-button"
                class="link-selector-selected-value-remove-button"
                @pointerup=${remove_current_selection}
            >
                ×
            </span>
        `,
        document_fragment
    );

    const remove_all_values_button = document_fragment.firstElementChild;
    if (remove_all_values_button !== null && document_fragment.children.length === 1) {
        return remove_all_values_button;
    }
    throw new Error("Cannot create the 'remove all values' button");
};
