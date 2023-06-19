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

import { dispatch, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { HostElement, SelectionElement } from "./SelectionElement";

const onClick = (host: HostElement): void => {
    host.clearSelection();
    dispatch(host, "clear-selection");
    dispatch(host, "open-dropdown");
};

export const getClearSelectionButton = (): UpdateFunction<SelectionElement> => {
    return html`
        <button
            type="button"
            data-test="clear-current-selection-button"
            class="lazybox-selected-value-remove-button"
            onclick=${onClick}
        >
            ×
        </button>
    `;
};
