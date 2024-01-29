/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
import type { SearchInput } from "./SearchInput";
import { TAG as SEARCH_TAG } from "./SearchInput";
import type { DropdownElement } from "./dropdown/DropdownElement";
import { TAG as DROPDOWN_TAG } from "./dropdown/DropdownElement";
import type { LazyAutocompleterOptions } from "./Options";
import type { GroupCollection, LazyboxItem } from "./GroupCollection";
import { Selection } from "./autocompleter/Selection";
import type { HandleSelection } from "./selection/HandleSelection";

export const TAG = "tuleap-lazy-autocompleter";

export type LazyAutocompleter = {
    options: LazyAutocompleterOptions;
    disabled: boolean;
    replaceContent(groups: GroupCollection): void;
};

type InternalLazyAutocompleter = Readonly<LazyAutocompleter> & {
    readonly search_input_element: SearchInput & HTMLElement;
    readonly selection: HandleSelection;
    readonly dropdown_element: DropdownElement & HTMLElement;
};

export type HostElement = InternalLazyAutocompleter & HTMLElement;

const isSearchInput = (element: HTMLElement): element is HTMLElement & SearchInput =>
    element.tagName === SEARCH_TAG.toUpperCase();

const isDropdown = (element: HTMLElement): element is HTMLElement & DropdownElement =>
    element.tagName === DROPDOWN_TAG.toUpperCase();

export const buildReplaceContent =
    (host: InternalLazyAutocompleter) =>
    (groups: GroupCollection): void => {
        host.dropdown_element.groups = groups;
    };

export const buildObserveDisabled = (
    host: InternalLazyAutocompleter,
    is_disabled: boolean,
    was_disabled = false,
): void => {
    if (is_disabled === was_disabled) {
        return;
    }

    host.search_input_element.disabled = is_disabled;

    if (is_disabled) {
        host.search_input_element.clear();
    }
};

export const getSearchInput = (host: HostElement): SearchInput & HTMLElement => {
    const element = host.ownerDocument.createElement(SEARCH_TAG);
    if (!isSearchInput(element)) {
        throw Error("Could not create search input");
    }
    element.disabled = false;
    element.placeholder = host.options.placeholder;

    const classname = "lazybox-single-search-section";
    element.classList.add(classname);
    element.setAttribute("tabindex", "0");
    element.addEventListener("search-input", () => {
        if (host.disabled) {
            return;
        }

        host.options.search_input_callback(element.getQuery());
    });
    return element;
};

const getSelection = (host: HostElement): HandleSelection => {
    const onSelection = (item: LazyboxItem): void => {
        host.options.selection_callback(item.value);
    };
    return Selection(onSelection);
};

export const getDropdownElement = (host: HostElement): DropdownElement & HTMLElement => {
    const element = host.ownerDocument.createElement(DROPDOWN_TAG);
    if (!isDropdown(element)) {
        throw Error("Could not create the dropdown element");
    }
    element.classList.add("lazybox-autocompleter");
    element.multiple_selection = false;
    element.templating_callback = host.options.templating_callback;

    element.selection = host.selection;
    element.search_input = host.search_input_element;
    element.open = true;
    return element;
};

define<InternalLazyAutocompleter>({
    tag: TAG,
    disabled: {
        value: false,
        observe: buildObserveDisabled,
    },
    options: undefined,
    replaceContent: { get: buildReplaceContent },
    search_input_element: { get: getSearchInput },
    selection: { get: getSelection },
    dropdown_element: { get: getDropdownElement },
    content: (host) => html`${host.dropdown_element}`,
});
