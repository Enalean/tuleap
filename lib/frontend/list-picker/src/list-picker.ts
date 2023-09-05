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

import type { ListPicker, ListPickerOptions, SelectionManager } from "./type";
import { DropdownContentRenderer } from "./renderers/DropdownContentRenderer";
import { EventManager } from "./events/EventManager";
import { DropdownManager } from "./dropdown/DropdownManager";
import { BaseComponentRenderer } from "./renderers/BaseComponentRenderer";
import { SingleSelectionManager } from "./selection/SingleSelectionManager";
import { MultipleSelectionManager } from "./selection/MultipleSelectionManager";
import { hideSourceSelectBox } from "./helpers/hide-selectbox-helper";
import { KeyboardNavigationManager } from "./navigation/KeyboardNavigationManager";
import { ListItemHighlighter } from "./navigation/ListItemHighlighter";
import { ItemsMapManager } from "./items/ItemsMapManager";
import { ListOptionsChangesObserver } from "./events/ListOptionsChangesObserver";
import { ListItemMapBuilder } from "./items/ListItemMapBuilder";
import type { GettextProvider } from "@tuleap/gettext";
import { ScrollingManager } from "./events/ScrollingManager";
import { FieldFocusManager } from "./navigation/FieldFocusManager";

export function createListPicker(
    source_select_box: HTMLSelectElement,
    gettext_provider: GettextProvider,
    options?: ListPickerOptions,
): ListPicker {
    hideSourceSelectBox(source_select_box);

    const items_map_manager = new ItemsMapManager(
        new ListItemMapBuilder(source_select_box, options),
    );
    items_map_manager.refreshItemsMap();
    const base_renderer = new BaseComponentRenderer(document, source_select_box, options);
    const {
        wrapper_element,
        list_picker_element,
        dropdown_element,
        selection_element,
        placeholder_element,
        dropdown_list_element,
        search_field_element,
        element_attributes_updater,
    } = base_renderer.renderBaseComponent();

    const scrolling_manager = new ScrollingManager(wrapper_element);
    const field_focus_manager = new FieldFocusManager(
        document,
        source_select_box,
        selection_element,
        search_field_element,
    );
    field_focus_manager.init();

    const dropdown_manager = new DropdownManager(
        document,
        wrapper_element,
        list_picker_element,
        dropdown_element,
        dropdown_list_element,
        selection_element,
        scrolling_manager,
        field_focus_manager,
    );

    let selection_manager: SelectionManager;

    let none_item;
    if (options?.none_value) {
        none_item = items_map_manager.getItemWithValue(options?.none_value);
    }

    if (source_select_box.multiple) {
        selection_manager = new MultipleSelectionManager(
            source_select_box,
            selection_element,
            search_field_element,
            options?.placeholder ?? "",
            dropdown_manager,
            items_map_manager,
            gettext_provider,
            none_item ?? null,
        );
    } else {
        selection_manager = new SingleSelectionManager(
            source_select_box,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_manager,
            items_map_manager,
        );
    }

    const dropdown_content_renderer = new DropdownContentRenderer(
        source_select_box,
        dropdown_list_element,
        items_map_manager,
        gettext_provider,
    );

    dropdown_content_renderer.renderListPickerDropdownContent();

    const highlighter = new ListItemHighlighter(dropdown_list_element);
    const keyboard_navigation_manager = new KeyboardNavigationManager(
        dropdown_list_element,
        highlighter,
    );
    const event_manager = new EventManager(
        document,
        wrapper_element,
        list_picker_element,
        dropdown_element,
        search_field_element,
        source_select_box,
        selection_manager,
        dropdown_manager,
        dropdown_content_renderer,
        keyboard_navigation_manager,
        highlighter,
        field_focus_manager,
    );

    event_manager.attachEvents();
    selection_manager.initSelection();

    const list_options_observer = new ListOptionsChangesObserver(
        source_select_box,
        element_attributes_updater,
        items_map_manager,
        dropdown_content_renderer,
        selection_manager,
        event_manager,
    );
    list_options_observer.startWatchingChanges();

    return {
        destroy: (): void => {
            list_options_observer.stopWatchingChangesInSelectOptions();
            event_manager.removeEventsListenersOnDocument();
            dropdown_manager.destroy();
            document.body.removeChild(dropdown_element);
            field_focus_manager.destroy();
        },
    };
}
