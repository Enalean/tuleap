/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { InternalSelectorsDropdown, SelectorEntry } from "./SelectorsDropdown";

export const DROPDOWN_BUTTON_CLASSNAME = "selectors-dropdown-button";
export const DROPDOWN_CONTENT_CLASSNAME = "selectors-dropdown-content";

const renderMenuItem = (entry: SelectorEntry): UpdateFunction<InternalSelectorsDropdown> => html`
    <a href="#" class="tlp-dropdown-menu-item" role="menuitem" data-test="menu-item"
        >${entry.entry_name}</a
    >
`;

export const renderContent = (
    host: InternalSelectorsDropdown,
): UpdateFunction<InternalSelectorsDropdown> => html`
    <div class="tlp-dropdown">
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline ${DROPDOWN_BUTTON_CLASSNAME}"
            data-test="dropdown-button"
        >
            <i class="fa-solid fa-plus tlp-button-icon" aria-hidden="true"></i>${host.button_text}
        </button>
        <div
            class="tlp-dropdown-menu ${DROPDOWN_CONTENT_CLASSNAME}"
            role="menu"
            data-test="dropdown-menu"
        >
            ${host.selectors_entries.map(renderMenuItem)}
        </div>
    </div>
`;
