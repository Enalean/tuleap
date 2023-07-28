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

import { define, html } from "hybrids";
import { autoUpdate, computePosition, flip } from "@floating-ui/dom";
import type { LazyboxOptions } from "./Options";
import type { GroupCollection, LazyboxItem } from "./GroupCollection";
import type { DropdownElement } from "./dropdown/DropdownElement";
import { TAG as DROPDOWN_TAG } from "./dropdown/DropdownElement";
import type { SearchInput } from "./SearchInput";
import { TAG as SEARCH_TAG } from "./SearchInput";
import type { SelectionElement } from "./selection/SelectionElement";
import { TAG as SELECTION_TAG } from "./selection/SelectionElement";
import { getSelectionBadgeCallback } from "./SelectionBadgeCallbackDefaulter";
import { isEnterKey, isEscapeKey } from "./helpers/keys-helper";
import { ScrollingManager } from "./events/ScrollingManager";

export const TAG = "tuleap-lazybox";

export type Lazybox = {
    options: LazyboxOptions;
    replaceDropdownContent(groups: GroupCollection): void;
    clearSelection(): void;
    replaceSelection(selection: ReadonlyArray<LazyboxItem>): void;
};
type InternalLazyboxElement = Readonly<Lazybox> & {
    tabindex: 0 | -1;
    internals: ElementInternals;
    readonly search_input_element: SearchInput & HTMLElement;
    readonly selection_element: SelectionElement & HTMLElement;
    readonly dropdown_element: DropdownElement & HTMLElement;
    scrolling_manager: ScrollingManager | undefined;
    cleanupAutoUpdate(): void;
};
export type HostElement = InternalLazyboxElement & HTMLElement;

const isSearchInput = (element: HTMLElement): element is HTMLElement & SearchInput =>
    element.tagName === SEARCH_TAG.toUpperCase();

const isSelection = (element: HTMLElement): element is HTMLElement & SelectionElement =>
    element.tagName === SELECTION_TAG.toUpperCase();

const isDropdown = (element: HTMLElement): element is HTMLElement & DropdownElement =>
    element.tagName === DROPDOWN_TAG.toUpperCase();

const noop = (): void => {
    //Do nothing
};

const onHostKeyUp = (host: InternalLazyboxElement, event: KeyboardEvent): void => {
    if (!isEnterKey(event)) {
        return;
    }
    host.dropdown_element.open = true;
};

const onHostPointerUp = (host: HostElement, event: Event): void => {
    if (event.composedPath().includes(host.dropdown_element)) {
        return;
    }
    host.dropdown_element.open = !host.dropdown_element.open;
};

const onDocumentKeyUp = (host: InternalLazyboxElement, event: KeyboardEvent): void => {
    if (!isEscapeKey(event)) {
        return;
    }
    event.stopPropagation();
    host.dropdown_element.open = false;
};

const onDocumentPointerUp = (host: HostElement, event: Event): void => {
    if (event.composedPath().includes(host)) {
        return;
    }
    host.dropdown_element.open = false;
};

type DisconnectFunction = () => void;
export const connect = (host: HostElement): DisconnectFunction => {
    const scrolling_manager = new ScrollingManager(host);
    host.scrolling_manager = scrolling_manager;
    const hostKeyupHandler = (event: KeyboardEvent): void => onHostKeyUp(host, event);
    const hostPointerUpHandler = (event: Event): void => onHostPointerUp(host, event);
    host.addEventListener("keyup", hostKeyupHandler);
    host.addEventListener("pointerup", hostPointerUpHandler);

    const documentKeyupHandler = (event: KeyboardEvent): void => onDocumentKeyUp(host, event);
    const documentPointerHandler = (event: Event): void => onDocumentPointerUp(host, event);

    host.ownerDocument.addEventListener("keyup", documentKeyupHandler);
    host.ownerDocument.addEventListener("pointerup", documentPointerHandler);
    return (): void => {
        scrolling_manager.unlockScrolling();
        host.removeEventListener("keyup", hostKeyupHandler);
        host.removeEventListener("pointerup", hostPointerUpHandler);
        host.ownerDocument.removeEventListener("keyup", documentKeyupHandler);
        host.ownerDocument.removeEventListener("pointerup", documentPointerHandler);
    };
};

const compute = (host: HostElement): void => {
    computePosition(host, host.dropdown_element, {
        placement: "bottom-start",
        middleware: [flip()],
    }).then(({ x, y, placement }) => {
        const width = host.getBoundingClientRect().width;
        Object.assign(host.dropdown_element.style, {
            width: `${width}px`,
            left: `${x}px`,
            top: `${y}px`,
        });
        const is_above = placement.includes("top");
        host.dropdown_element.classList.toggle("lazybox-dropdown-above", is_above);
        host.selection_element.classList.toggle("lazybox-with-dropdown-above", is_above);
    });
};

export const buildReplaceDropdown =
    (host: InternalLazyboxElement) =>
    (groups: GroupCollection): void => {
        host.dropdown_element.groups = groups;
    };

export const buildClearSelection = (host: InternalLazyboxElement) => (): void => {
    host.selection_element.clearSelection();
};

export const buildReplaceSelection =
    (host: InternalLazyboxElement) =>
    (selection: ReadonlyArray<LazyboxItem>): void => {
        host.selection_element.replaceSelection(selection);
    };

export const getSearchInput = (host: HostElement): SearchInput & HTMLElement => {
    const element = host.ownerDocument.createElement(SEARCH_TAG);
    if (!isSearchInput(element)) {
        throw Error("Could not create search input");
    }
    element.disabled = false;
    element.placeholder = host.options.is_multiple
        ? host.options.placeholder
        : host.options.search_input_placeholder;
    const classname = host.options.is_multiple
        ? "lazybox-multiple-search-section"
        : "lazybox-single-search-section";
    element.classList.add(classname);
    element.setAttribute("tabindex", "0");
    element.addEventListener("search-entered", () => {
        host.dropdown_element.open = true;
    });
    element.addEventListener("search-input", () => {
        host.options.search_input_callback(element.getQuery());
    });
    return element;
};

export const getSelectionElement = (host: HostElement): SelectionElement & HTMLElement => {
    const element = host.ownerDocument.createElement(SELECTION_TAG);
    if (!isSelection(element)) {
        throw Error("Could not create selection element");
    }
    element.multiple = host.options.is_multiple;
    element.placeholder_text = host.options.placeholder;
    element.search_input = host.search_input_element;
    element.onSelection = host.options.selection_callback;
    element.templating_callback = host.options.templating_callback;
    element.selection_badge_callback = getSelectionBadgeCallback(host.options);
    element.setAttribute("data-test", "lazybox");
    if (host.options.is_multiple) {
        element.role = "combobox";
        element.ariaHasPopup = "true";
        element.ariaExpanded = "false";
    } else {
        element.role = "textbox";
        element.ariaReadOnly = "true";
    }

    element.addEventListener("clear-selection", () => {
        host.search_input_element.clear();
    });
    element.addEventListener("open-dropdown", () => {
        host.dropdown_element.open = true;
    });
    return element;
};

const onOpen = (host: HostElement): void => {
    host.scrolling_manager?.lockScrolling();
    host.selection_element.classList.add("lazybox-with-open-dropdown");
    host.cleanupAutoUpdate = autoUpdate(host, host.dropdown_element, () => compute(host));
    host.search_input_element.focus();
};

const onClose = (host: HostElement): void => {
    host.scrolling_manager?.unlockScrolling();
    host.selection_element.classList.remove("lazybox-with-open-dropdown");
    host.search_input_element.clear();
    host.cleanupAutoUpdate();
    host.focus();
};

export const getDropdownElement = (host: HostElement): DropdownElement & HTMLElement => {
    const element = host.ownerDocument.createElement(DROPDOWN_TAG);
    if (!isDropdown(element)) {
        throw Error("Could not create the dropdown element");
    }
    element.classList.add("lazybox-dropdown");
    element.multiple_selection = host.options.is_multiple;
    element.templating_callback = host.options.templating_callback;
    if (host.options.new_item_clicked_callback !== undefined) {
        element.has_new_item = true;
        element.new_item_button_label = host.options.new_item_label_callback("");
        host.search_input_element.addEventListener("search-input", () => {
            if (host.options.new_item_clicked_callback === undefined) {
                return;
            }
            element.new_item_button_label = host.options.new_item_label_callback(
                host.search_input_element.getQuery()
            );
        });
        element.addEventListener("click-create-item", () => {
            host.options.new_item_clicked_callback?.(host.search_input_element.getQuery());
            host.search_input_element.clear();
        });
    }
    element.selection = host.selection_element;
    element.search_input = host.search_input_element;
    element.addEventListener("open", () => onOpen(host));
    element.addEventListener("close", () => onClose(host));
    return element;
};

const IntermediaryClass = define.compile<InternalLazyboxElement>({
    tag: TAG,
    tabindex: 0,
    options: undefined,
    replaceDropdownContent: { get: buildReplaceDropdown, connect },
    clearSelection: { get: buildClearSelection },
    replaceSelection: { get: buildReplaceSelection },
    cleanupAutoUpdate: { set: (host, new_value) => new_value ?? noop },
    search_input_element: { get: getSearchInput },
    selection_element: { get: getSelectionElement },
    dropdown_element: { get: getDropdownElement },
    scrolling_manager: undefined,
    internals: { get: (host) => host.attachInternals() },
    content: (host) => html`${host.selection_element}${host.dropdown_element}`,
});

export class LazyboxElement extends IntermediaryClass {
    static formAssociated = true;
}

if (!window.customElements.get(TAG)) {
    window.customElements.define(TAG, LazyboxElement);
}
