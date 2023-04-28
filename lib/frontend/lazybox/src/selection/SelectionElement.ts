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

import type { UpdateFunction } from "hybrids";
import { define, dispatch, html } from "hybrids";
import { html as lit_html, render } from "lit/html.js";
import type { TemplateResult } from "lit/html.js";
import type {
    LazyboxSelectionBadgeCallback,
    LazyboxSelectionCallback,
    RenderedItem,
} from "../type";
import { isEnterKey } from "../helpers/keys-helper";
import type { SearchInput } from "../SearchInput";
import { getClearSelectionButton } from "./ClearSelectionTemplate";

export const TAG = "tuleap-lazybox-selection";

export type SelectionElement = {
    multiple: boolean;
    placeholder_text: string;
    selectItem(item: RenderedItem): void;
    clearSelection(): void;
    hasSelection(): boolean;
    onSelection: LazyboxSelectionCallback;
    getSelection(): ReadonlyArray<RenderedItem>;
    replaceSelection(items: ReadonlyArray<RenderedItem>): void;
    setFocus(): void;
    selection_badge_callback: LazyboxSelectionBadgeCallback;
    search_input: SearchInput & HTMLElement;
};
type InternalSelectionElement = Readonly<SelectionElement> & {
    content(): HTMLElement;
    selected_items: RenderedItem[];
    span_element: HTMLElement;
};
export type HostElement = InternalSelectionElement & HTMLElement;

const getSingleSelectedElement = (host: InternalSelectionElement): Element => {
    const root = document.createElement("span");
    root.setAttribute("data-test", "selected-element");
    root.classList.add("lazybox-selected-value");
    root.setAttribute("aria-readonly", "true");
    let template: TemplateResult;
    if (host.selected_items.length < 1) {
        template = lit_html``;
    } else {
        template = host.selected_items[0].template;
    }
    render(template, root);
    return root;
};

const getSingleSelectionContent = (
    host: InternalSelectionElement
): UpdateFunction<SelectionElement> => {
    if (!host.hasSelection()) {
        return html`<span class="lazybox-placeholder" data-test="selection-placeholder"
            >${host.placeholder_text}</span
        >`;
    }
    return html`${getSingleSelectedElement(host)}${getClearSelectionButton()}`;
};

const callOnSelection = (host: InternalSelectionElement): void => {
    const values = host.selected_items.map((item) => item.value);
    const callback_parameter = host.multiple ? values : values[0];
    host.onSelection(callback_parameter);
};

const removeItemFromSelection = (
    host: InternalSelectionElement,
    item_to_remove: RenderedItem
): void => {
    host.selected_items = host.selected_items.filter((item) => item !== item_to_remove);
    callOnSelection(host);
};

export const buildSelectedBadges = (host: HostElement): HTMLElement[] =>
    host.selected_items.map((selected_item) => {
        const badge = host.selection_badge_callback(selected_item);
        badge.addEventListener("remove-badge", () => {
            removeItemFromSelection(host, selected_item);
            dispatch(host, "open-dropdown");
        });
        return badge;
    });

const getMultipleSelectionContent = (host: HostElement): UpdateFunction<SelectionElement> => {
    if (!host.hasSelection()) {
        return html`${host.search_input}`;
    }
    return html`${buildSelectedBadges(host)}${host.search_input}${getClearSelectionButton()}`;
};

export const buildClear = (host: InternalSelectionElement) => (): void => {
    host.selected_items = [];
    const callback_parameter = host.multiple ? [] : null;
    host.onSelection(callback_parameter);
};

export const buildSelectItem =
    (host: InternalSelectionElement) =>
    (item: RenderedItem): void => {
        if (item.is_selected) {
            // We won't unselect it
            return;
        }
        if (item.is_disabled) {
            // We don't care ¯\_(ツ)_/¯
            return;
        }
        host.selected_items = [...host.selected_items, item];
        callOnSelection(host);
    };

export const buildReplaceSelection =
    (host: InternalSelectionElement) =>
    (items: ReadonlyArray<RenderedItem>): void => {
        host.selected_items = [...items];
        callOnSelection(host);
    };

const markDropdownItemAsSelected = (item: RenderedItem): void => {
    item.is_selected = true;
    item.element.setAttribute("aria-selected", "true");
};

const markDropdownItemAsNotSelected = (item: RenderedItem): void => {
    item.is_selected = false;
    item.element.setAttribute("aria-selected", "false");
};

export const selectedItemSetter = (
    host: SelectionElement,
    new_value: RenderedItem[],
    old_value: RenderedItem[] | undefined
): RenderedItem[] => {
    if (!old_value) {
        return [];
    }
    if (old_value === new_value) {
        return new_value;
    }
    old_value.forEach(markDropdownItemAsNotSelected);
    new_value.forEach(markDropdownItemAsSelected);
    if (host.multiple) {
        if (new_value.length > 0) {
            host.search_input.placeholder = "";
        } else {
            host.search_input.placeholder = host.placeholder_text;
        }
    }
    return new_value;
};

export const buildHasSelection = (host: InternalSelectionElement) => (): boolean =>
    host.selected_items.length > 0;

export const buildGetSelection =
    (host: InternalSelectionElement) => (): ReadonlyArray<RenderedItem> =>
        host.selected_items;

export const searchInputSetter = (
    host: InternalSelectionElement,
    new_value: SearchInput & HTMLElement
): SearchInput & HTMLElement => {
    new_value.addEventListener("backspace-pressed", () => {
        const last_item = host.selected_items[host.selected_items.length - 1];
        removeItemFromSelection(host, last_item);
    });
    return new_value;
};

export const buildFocus = (host: InternalSelectionElement) => (): void => host.span_element.focus();

export const onKeyUp = (host: HostElement, event: KeyboardEvent): void => {
    if (isEnterKey(event)) {
        event.stopPropagation();
        dispatch(host, "open-dropdown");
    }
};

export const getContent = (host: HostElement): UpdateFunction<InternalSelectionElement> => {
    if (host.multiple) {
        return html`<span
            class="lazybox-selection lazybox-multiple"
            data-lazybox-selection
            data-test="lazybox-selection"
            role="combobox"
            aria-haspopup="true"
            aria-expanded="false"
            tabindex="0"
            onkeyup="${onKeyUp}"
            >${getMultipleSelectionContent(host)}</span
        >`;
    }
    return html`<span
        class="lazybox-selection lazybox-single"
        data-lazybox-selection
        data-test="lazybox-selection"
        role="textbox"
        aria-readonly="true"
        tabindex="0"
        onkeyup="${onKeyUp}"
        >${getSingleSelectionContent(host)}</span
    >`;
};

export const getSpan = (host: InternalSelectionElement): HTMLElement => {
    const element = host.content().querySelector("[data-lazybox-selection]");
    if (!(element instanceof HTMLElement)) {
        throw Error("Could not find selection element");
    }
    return element;
};

export const SelectionElement = define<InternalSelectionElement>({
    tag: TAG,
    multiple: false,
    placeholder_text: "",
    selected_items: { set: selectedItemSetter },
    selectItem: { get: buildSelectItem },
    clearSelection: { get: buildClear },
    hasSelection: { get: buildHasSelection },
    getSelection: { get: buildGetSelection },
    replaceSelection: { get: buildReplaceSelection },
    setFocus: { get: buildFocus },
    onSelection: undefined,
    selection_badge_callback: undefined,
    search_input: { set: searchInputSetter },
    span_element: { get: getSpan },
    content: getContent,
});
