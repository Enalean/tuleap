/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { ItemsMapManager } from "../items/ItemsMapManager";
import type { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { SelectionManager } from "../selection/SelectionManager";
import type { EventManager } from "../events/EventManager";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import type { GroupCollection } from "../items/GroupCollection";

export interface DropdownContentRefresher {
    refresh(groups: GroupCollection): void;
}

export const DropdownContentRefresher = (
    items_map_manager: ItemsMapManager,
    dropdown_content_renderer: DropdownContentRenderer,
    selection_manager: SelectionManager,
    event_manager: EventManager,
    list_item_highlighter: ListItemHighlighter
): DropdownContentRefresher => ({
    refresh(groups: GroupCollection): void {
        items_map_manager.refreshItemsMap(groups);
        dropdown_content_renderer.renderLinkSelectorDropdownContent(groups);
        selection_manager.resetAfterDependenciesUpdate();
        event_manager.attachItemListEvent();
        list_item_highlighter.resetHighlight();
    },
});
