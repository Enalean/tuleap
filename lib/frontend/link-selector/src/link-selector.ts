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

import type { LinkSelector, LinkSelectorOptions } from "./type";
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
import { SearchFieldEventCallbackHandler } from "./events/SearchFieldEventCallbackHandler";
import { DropdownContentRefresher } from "./dropdown/DropdownContentRefresher";

export function createLinkSelector(
    source_select_box: HTMLSelectElement,
    options: LinkSelectorOptions
): LinkSelector {
    hideSourceSelectBox(source_select_box);

    const items_map_manager = new ItemsMapManager(ListItemMapBuilder(options.templating_callback));

    items_map_manager.refreshItemsMap([]);
    const base_renderer = new BaseComponentRenderer(
        document,
        source_select_box,
        options.placeholder ?? ""
    );
    const {
        wrapper_element,
        link_selector_element,
        dropdown_element,
        selection_element,
        placeholder_element,
        dropdown_list_element,
        search_field_element,
    } = base_renderer.renderBaseComponent();

    const scrolling_manager = new ScrollingManager(wrapper_element);
    const field_focus_manager = new FieldFocusManager(
        document,
        source_select_box,
        selection_element,
        search_field_element
    );
    field_focus_manager.init();

    const dropdown_manager = new DropdownManager(
        document,
        wrapper_element,
        link_selector_element,
        dropdown_element,
        dropdown_list_element,
        selection_element,
        scrolling_manager,
        field_focus_manager
    );

    const selection_manager = new SelectionManager(
        source_select_box,
        dropdown_element,
        selection_element,
        placeholder_element,
        dropdown_manager,
        items_map_manager,
        options.selection_callback
    );

    const dropdown_content_renderer = new DropdownContentRenderer(
        dropdown_list_element,
        items_map_manager
    );

    const highlighter = new ListItemHighlighter(dropdown_list_element);
    const keyboard_navigation_manager = new KeyboardNavigationManager(
        dropdown_list_element,
        highlighter
    );
    const event_manager = new EventManager(
        document,
        wrapper_element,
        link_selector_element,
        dropdown_element,
        search_field_element,
        source_select_box,
        selection_manager,
        dropdown_manager,
        dropdown_content_renderer,
        keyboard_navigation_manager,
        highlighter,
        field_focus_manager
    );
    const dropdown_content_refresher = DropdownContentRefresher(
        items_map_manager,
        dropdown_content_renderer,
        selection_manager,
        event_manager,
        highlighter
    );

    event_manager.attachEvents();

    const link_selector_instance: LinkSelector = {
        setDropdownContent: (groups): void => {
            dropdown_content_refresher.refresh(groups);
        },
        resetSelection: (): void => {
            selection_manager.clearSelection();
        },
        destroy: (): void => {
            event_manager.removeEventsListenersOnDocument();
            dropdown_manager.destroy();
            document.body.removeChild(dropdown_element);
            field_focus_manager.destroy();
        },
    };

    const search_event_handler = SearchFieldEventCallbackHandler(
        link_selector_instance,
        search_field_element,
        options.search_field_callback
    );
    search_event_handler.init();

    return link_selector_instance;
}
