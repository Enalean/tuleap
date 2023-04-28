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

import type { Lazybox, LazyboxOptions } from "./type";
import { DropdownContentRenderer } from "./renderers/DropdownContentRenderer";
import { EventManager } from "./events/EventManager";
import { DropdownManager } from "./dropdown/DropdownManager";
import { BaseComponentRenderer } from "./renderers/BaseComponentRenderer";
import { SelectionManager } from "./selection/SelectionManager";
import { hideSourceSelectBox } from "./helpers/hide-selectbox-helper";
import { KeyboardNavigationManager } from "./navigation/KeyboardNavigationManager";
import { ListItemHighlighter } from "./navigation/ListItemHighlighter";
import { ItemsMapManager } from "./items/ItemsMapManager";
import { ListItemMapBuilder } from "./items/ListItemMapBuilder";
import { ScrollingManager } from "./events/ScrollingManager";
import { FieldFocusManager } from "./navigation/FieldFocusManager";
import { DropdownContentRefresher } from "./dropdown/DropdownContentRefresher";
import type { LazyboxItem } from "./items/GroupCollection";
import { getSelectionBadgeCallback } from "./SelectionBadgeCallbackDefaulter";
import { DropdownEventsHandler } from "./dropdown/DropdownEventsHandler";
import { KeyboardSelector } from "./selection/KeyboardSelector";

export function createLazybox(
    source_select_box: HTMLSelectElement,
    options: LazyboxOptions
): Lazybox {
    hideSourceSelectBox(source_select_box);

    const list_items_builder = ListItemMapBuilder(options.templating_callback);
    const items_map_manager = new ItemsMapManager(list_items_builder);

    items_map_manager.refreshItemsMap([]);
    const base_renderer = new BaseComponentRenderer(document, source_select_box, options);
    const {
        wrapper_element,
        lazybox_element,
        dropdown_element,
        dropdown_list_element,
        search_field_element,
        selection_element,
    } = base_renderer.renderBaseComponent();
    selection_element.selection_badge_callback = getSelectionBadgeCallback(options);

    const scrolling_manager = new ScrollingManager(wrapper_element);
    const field_focus_manager = new FieldFocusManager(source_select_box, selection_element);
    field_focus_manager.init();

    const highlighter = new ListItemHighlighter(dropdown_list_element);
    const dropdown_events_handler = DropdownEventsHandler(
        scrolling_manager,
        search_field_element,
        selection_element,
        highlighter
    );
    const dropdown_manager = new DropdownManager(
        document,
        wrapper_element,
        lazybox_element,
        dropdown_element,
        dropdown_list_element,
        dropdown_events_handler.onDropdownOpen,
        dropdown_events_handler.onDropdownClosed
    );
    const selection_manager = new SelectionManager(selection_element, items_map_manager);
    const keyboard_selector = KeyboardSelector(
        dropdown_manager,
        highlighter,
        selection_manager,
        search_field_element
    );
    search_field_element.addEventListener("search-input", () => {
        dropdown_manager.openLazybox();
    });
    search_field_element.addEventListener("enter-pressed", () => {
        keyboard_selector.handleEnter();
    });
    selection_element.addEventListener("clear-selection", () => {
        search_field_element.clear();
        dropdown_manager.openLazybox();
    });
    selection_element.addEventListener("open-dropdown", () => {
        dropdown_manager.openLazybox();
    });

    const dropdown_content_renderer = new DropdownContentRenderer(
        dropdown_list_element,
        items_map_manager
    );

    const keyboard_navigation_manager = new KeyboardNavigationManager(
        dropdown_list_element,
        highlighter
    );
    const event_manager = new EventManager(
        document,
        wrapper_element,
        lazybox_element,
        dropdown_element,
        search_field_element,
        source_select_box,
        selection_manager,
        dropdown_manager,
        keyboard_navigation_manager,
        highlighter,
        keyboard_selector
    );
    const dropdown_content_refresher = DropdownContentRefresher(
        items_map_manager,
        dropdown_content_renderer,
        selection_manager,
        event_manager,
        highlighter
    );

    event_manager.attachEvents();

    return {
        setDropdownContent: (groups): void => {
            dropdown_content_refresher.refresh(groups);
        },
        resetSelection: (): void => {
            search_field_element.clear();
            selection_manager.clearSelection();
        },
        setSelection: (selection: ReadonlyArray<LazyboxItem>): void => {
            const rendered_items = selection.map((item_to_select) =>
                list_items_builder.buildRenderedItem(item_to_select, "")
            );
            selection_manager.setSelection(rendered_items);
        },
        destroy: (): void => {
            scrolling_manager.unlockScrolling();
            event_manager.removeEventsListenersOnDocument();
            dropdown_manager.destroy();
            document.body.removeChild(dropdown_element);
            field_focus_manager.destroy();
        },
    };
}
