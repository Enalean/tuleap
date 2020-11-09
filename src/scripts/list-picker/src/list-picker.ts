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

import { ListPicker, ListPickerOptions } from "./type";
import { DropdownContentRenderer } from "./renderers/DropdownContentRenderer";
import { EventManager } from "./events/EventManager";
import { DropdownToggler } from "./dropdown/DropdownToggler";
import { BaseComponentRenderer } from "./renderers/BaseComponentRenderer";
import { getPOFileFromLocale, initGettext } from "../../tuleap/gettext/gettext-init";
import { SingleSelectionManager } from "./selection/SingleSelectionManager";
import { MultipleSelectionManager } from "./selection/MultipleSelectionManager";
import { hideSourceSelectBox } from "./helpers/hide-selectbox-helper";
import { KeyboardNavigationManager } from "./navigation/KeyboardNavigationManager";
import { ListItemHighlighter } from "./navigation/ListItemHighlighter";
import { ItemsMapManager } from "./items/ItemsMapManager";
import { ListOptionsChangesObserver } from "./events/ListOptionsChangesObserver";
import { ListItemMapBuilder } from "./items/ListItemMapBuilder";

export async function createListPicker(
    source_select_box: HTMLSelectElement,
    options?: ListPickerOptions
): Promise<ListPicker> {
    hideSourceSelectBox(source_select_box);

    let language = document.body.dataset.userLocale;
    if (language === undefined) {
        language = "en_US";
    }

    const gettext_provider = await initGettext(
        language,
        "tuleap-list-picker",
        (locale) =>
            import(/* webpackChunkName: "list-picker-po-" */ "../po/" + getPOFileFromLocale(locale))
    );

    const items_map_manager = new ItemsMapManager(
        new ListItemMapBuilder(source_select_box, options)
    );
    await items_map_manager.refreshItemsMap();
    const base_renderer = new BaseComponentRenderer(source_select_box, options);
    const {
        wrapper_element,
        list_picker_element,
        dropdown_element,
        selection_element,
        placeholder_element,
        dropdown_list_element,
        search_field_element,
    } = base_renderer.renderBaseComponent();

    const dropdown_toggler = new DropdownToggler(
        list_picker_element,
        dropdown_element,
        dropdown_list_element,
        search_field_element,
        selection_element
    );

    let selection_manager;
    if (source_select_box.multiple) {
        selection_manager = new MultipleSelectionManager(
            source_select_box,
            selection_element,
            search_field_element,
            options?.placeholder ?? "",
            dropdown_toggler,
            items_map_manager,
            gettext_provider
        );
    } else {
        selection_manager = new SingleSelectionManager(
            source_select_box,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_toggler,
            items_map_manager
        );
    }

    const dropdown_content_renderer = new DropdownContentRenderer(
        source_select_box,
        dropdown_list_element,
        items_map_manager,
        gettext_provider
    );

    dropdown_content_renderer.renderListPickerDropdownContent();

    const highlighter = new ListItemHighlighter(dropdown_list_element);
    const keyboard_navigation_manager = new KeyboardNavigationManager(
        dropdown_list_element,
        dropdown_toggler,
        highlighter
    );
    const event_manager = new EventManager(
        document,
        wrapper_element,
        dropdown_element,
        search_field_element,
        source_select_box,
        selection_manager,
        dropdown_toggler,
        dropdown_content_renderer,
        keyboard_navigation_manager,
        highlighter
    );

    event_manager.attachEvents();
    selection_manager.initSelection();

    const list_options_observer = new ListOptionsChangesObserver(
        source_select_box,
        items_map_manager,
        dropdown_content_renderer,
        selection_manager,
        event_manager
    );
    list_options_observer.startWatchingChangesInSelectOptions();

    return {
        destroy: (): void => {
            list_options_observer.stopWatchingChangesInSelectOptions();
            event_manager.removeEventsListenersOnDocument();
        },
    };
}
