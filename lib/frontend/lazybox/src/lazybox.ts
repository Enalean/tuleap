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

import { autoUpdate, computePosition, flip } from "@floating-ui/dom";
import type { Lazybox, LazyboxOptions } from "./type";
import { EventManager } from "./events/EventManager";
import { BaseComponentRenderer } from "./renderers/BaseComponentRenderer";
import { hideSourceSelectBox } from "./helpers/hide-selectbox-helper";
import { ScrollingManager } from "./events/ScrollingManager";
import { FieldFocusManager } from "./navigation/FieldFocusManager";
import type { LazyboxItem } from "./items/GroupCollection";
import { getSelectionBadgeCallback } from "./SelectionBadgeCallbackDefaulter";

export function createLazybox(
    source_select_box: HTMLSelectElement,
    options: LazyboxOptions
): Lazybox {
    hideSourceSelectBox(source_select_box);

    const base_renderer = new BaseComponentRenderer(document, source_select_box, options);
    const {
        wrapper_element,
        lazybox_element,
        dropdown_element,
        search_field_element,
        selection_element,
    } = base_renderer.renderBaseComponent();
    selection_element.selection_badge_callback = getSelectionBadgeCallback(options);

    const scrolling_manager = new ScrollingManager(wrapper_element);
    const field_focus_manager = new FieldFocusManager(source_select_box, selection_element);
    field_focus_manager.init();

    const compute = (): void => {
        computePosition(wrapper_element, dropdown_element, {
            placement: "bottom-start",
            middleware: [flip()],
        }).then(({ x, y, placement }) => {
            const width = wrapper_element.getBoundingClientRect().width;
            Object.assign(dropdown_element.style, {
                width: `${width}px`,
                left: `${x}px`,
                top: `${y}px`,
            });
            const is_above = placement.includes("top");
            dropdown_element.classList.toggle("lazybox-dropdown-above", is_above);
            selection_element.classList.toggle("lazybox-with-dropdown-above", is_above);
        });
    };
    let cleanup = (): void => {
        //Do nothing by default
    };

    dropdown_element.addEventListener("open", () => {
        scrolling_manager.lockScrolling();
        selection_element.classList.add("lazybox-with-open-dropdown");
        cleanup = autoUpdate(wrapper_element, dropdown_element, compute);
    });
    dropdown_element.addEventListener("close", () => {
        scrolling_manager.unlockScrolling();
        selection_element.classList.remove("lazybox-with-open-dropdown");
        search_field_element.clear();
        cleanup();
    });
    search_field_element.addEventListener("search-input", () => {
        dropdown_element.open = true;
    });
    selection_element.addEventListener("clear-selection", () => {
        search_field_element.clear();
    });

    const event_manager = new EventManager(
        document,
        wrapper_element,
        lazybox_element,
        dropdown_element,
        source_select_box
    );
    event_manager.attachEvents();

    return {
        setDropdownContent: (groups): void => {
            dropdown_element.groups = groups;
        },
        resetSelection: (): void => {
            search_field_element.clear();
            selection_element.clearSelection();
        },
        replaceSelection: (selection: ReadonlyArray<LazyboxItem>): void => {
            selection_element.replaceSelection(selection);
        },
        destroy: (): void => {
            scrolling_manager.unlockScrolling();
            event_manager.removeEventsListenersOnDocument();
            document.body.removeChild(dropdown_element);
            field_focus_manager.destroy();
        },
    };
}
