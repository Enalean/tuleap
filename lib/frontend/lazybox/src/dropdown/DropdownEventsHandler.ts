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

import type { ScrollingManager } from "../events/ScrollingManager";
import type { SearchInput } from "../SearchInput";
import type { FieldFocusManager } from "../navigation/FieldFocusManager";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import type { SelectionElement } from "../selection/SelectionElement";

export type DropdownEventsHandler = {
    onDropdownOpen(): void;
    onDropdownClosed(): void;
};

export const DropdownEventsHandler = (
    scrolling_manager: ScrollingManager,
    search_field: SearchInput,
    single_selection_element: SelectionElement,
    field_focus_manager: FieldFocusManager,
    highlighter: ListItemHighlighter
): DropdownEventsHandler => ({
    onDropdownOpen(): void {
        scrolling_manager.lockScrolling();
        search_field.setFocus();
    },

    onDropdownClosed(): void {
        scrolling_manager.unlockScrolling();
        field_focus_manager.applyFocusOnLazybox();
        single_selection_element.setFocus();
        highlighter.resetHighlight();
    },
});
