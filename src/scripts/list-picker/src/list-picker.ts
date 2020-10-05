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
import { DropdownContentRenderer } from "./helpers/DropdownContentRenderer";
import { EventManager } from "./helpers/EventManager";
import { DropdownToggler } from "./helpers/DropdownToggler";
import { BaseComponentRenderer } from "./renderers/BaseComponentRenderer";
import { generateItemMapBasedOnSourceSelectOptions } from "./helpers/static-list-helper";
import { getPOFileFromLocale, initGettext } from "../../tuleap/gettext/gettext-init";
import { SingleSelectionManager } from "./selection/SingleSelectionManager";
import { MultipleSelectionManager } from "./selection/MultipleSelectionManager";
import { hideSourceSelectBox } from "./helpers/hide-selectbox-helper";

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

    const item_map = generateItemMapBasedOnSourceSelectOptions(source_select_box);
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
            item_map,
            gettext_provider
        );
    } else {
        selection_manager = new SingleSelectionManager(
            source_select_box,
            dropdown_element,
            selection_element,
            placeholder_element,
            dropdown_toggler,
            item_map
        );
    }

    const dropdown_content_renderer = new DropdownContentRenderer(
        source_select_box,
        dropdown_list_element,
        item_map,
        gettext_provider
    );

    dropdown_content_renderer.renderListPickerDropdownContent();

    const event_manager = new EventManager(
        document,
        wrapper_element,
        dropdown_element,
        search_field_element,
        source_select_box,
        selection_manager,
        dropdown_toggler,
        dropdown_content_renderer
    );

    event_manager.attachEvents();
    selection_manager.initSelection();

    return {
        destroy: (): void => event_manager.removeEventsListenersOnDocument(),
    };
}
