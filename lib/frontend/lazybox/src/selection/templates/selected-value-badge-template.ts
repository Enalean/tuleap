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
import type { RenderedItem } from "../../type";

export type RemoveCurrentValueFromSelectionCallback = (event: Event) => void;

export const buildSelectedValueBadgeElement = (
    item: RenderedItem,
    remove_value_from_selection: RemoveCurrentValueFromSelectionCallback
): HTMLElement => {
    const document_fragment = document.createDocumentFragment();
    render(
        html`
            <span
                data-test="lazybox-selected-value"
                class="lazybox-selected-value-badge tlp-badge-primary tlp-badge-outline"
                data-item-id="${item.id}"
            >
                <span
                    role="presentation"
                    data-test="remove-value-button"
                    class="lazybox-value-remove-button"
                    @pointerup=${remove_value_from_selection}
                >
                    ×
                </span>
                ${item.template}
            </span>
        `,
        document_fragment
    );

    const selected_value_badge = document_fragment.firstElementChild;
    if (selected_value_badge instanceof HTMLElement && document_fragment.children.length === 1) {
        return selected_value_badge;
    }
    throw new Error("Cannot create the selected value badge");
};
