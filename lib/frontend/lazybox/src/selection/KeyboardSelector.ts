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

import type { ManageDropdown } from "../dropdown/DropdownManager";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import type { SearchInput } from "../SearchInput";
import type { ManageSelection } from "../type";

export type KeyboardSelector = {
    handleEnter(): void;
};

export const KeyboardSelector = (
    dropdown_manager: ManageDropdown,
    highlighter: ListItemHighlighter,
    selection_manager: ManageSelection,
    search_input: SearchInput
): KeyboardSelector => ({
    handleEnter(): void {
        if (!dropdown_manager.isDropdownOpen()) {
            return;
        }
        const highlighted_item = highlighter.getHighlightedItem();
        if (!highlighted_item) {
            return;
        }
        selection_manager.processSelection(highlighted_item);
        dropdown_manager.closeLazybox();
        search_input.clear();
    },
});
