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

import { render, html } from "lit/html.js";
import type { LazyBoxWithNewItemButton } from "../type";

export const getNewItemTemplate = (
    doc: Document,
    options: LazyBoxWithNewItemButton
): HTMLElement => {
    const document_fragment = doc.createDocumentFragment();
    render(
        html`<button
            type="button"
            class="lazybox-new-item-button"
            @pointerup="${options.new_item_callback}"
        >
            ${options.new_item_button_label}
        </button>`,
        document_fragment
    );

    const button = document_fragment.querySelector("button");
    if (button === null) {
        throw Error("Could not create the 'new item' button");
    }
    return button;
};
