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
import type { RenderedItem, LazyboxSelectionCallback } from "../type";
import { isEnterKey } from "../helpers/keys-helper";

export const TAG = "tuleap-lazybox-selection-single";

export type SelectionElement = {
    placeholder_text: string;
    selectItem(item: RenderedItem): void;
    clearSelection(): void;
    hasSelection(): boolean;
    onSelection: LazyboxSelectionCallback;
    getSelection(): RenderedItem | undefined;
    setFocus(): void;
};
type InternalSelectionElement = Readonly<SelectionElement> & {
    selected_item: RenderedItem | undefined;
    selected_item_element: HTMLElement;
    span_element: HTMLElement;
    content(): HTMLElement;
};
export type HostElement = InternalSelectionElement & HTMLElement;

const onClick = (host: HostElement, event: Event): void => {
    event.stopPropagation();
    host.clearSelection();
    dispatch(host, "clear-selection");
};

const onKeyDownInButton = (host: unknown, event: KeyboardEvent): void => {
    if (isEnterKey(event)) {
        // Do not trigger the click, or else the dropdown will open and be focused,
        // "keyup" will be dispatched in it, and it will immediately select the first possible value
        event.preventDefault();
    }
};

const onKeyUpInButton = (host: HostElement, event: KeyboardEvent): void => {
    if (isEnterKey(event)) {
        onClick(host, event);
    }
};

export const getClearSelectionButton = (
    host: HostElement
): UpdateFunction<InternalSelectionElement> => {
    if (!host.selected_item) {
        return html``;
    }
    return html`
        <button
            type="button"
            data-test="clear-current-selection-button"
            class="lazybox-selected-value-remove-button"
            onclick=${onClick}
            onkeydown="${onKeyDownInButton}"
            onkeyup="${onKeyUpInButton}"
        >
            ×
        </button>
    `;
};

const getContent = (host: InternalSelectionElement): UpdateFunction<SelectionElement> => {
    if (!host.selected_item) {
        return html`<span class="lazybox-placeholder" data-test="selection-placeholder"
            >${host.placeholder_text}</span
        >`;
    }

    return html`${host.selected_item_element}`;
};

export const onKeyUp = (host: HostElement, event: KeyboardEvent): void => {
    if (isEnterKey(event)) {
        event.stopPropagation();
        dispatch(host, "enter-pressed");
    }
};

export const buildClear = (host: InternalSelectionElement): (() => void) => {
    return (): void => {
        host.selected_item = undefined;
        host.onSelection(null);
    };
};

export const buildSet = (host: InternalSelectionElement): ((item: RenderedItem) => void) => {
    return (item: RenderedItem): void => {
        if (item.is_selected) {
            // We won't unselect it
            return;
        }

        if (item.is_disabled) {
            // We don't care ¯\_(ツ)_/¯
            return;
        }

        host.selected_item = item;
        host.onSelection(item.value);
    };
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
    host: unknown,
    new_value: RenderedItem | undefined,
    old_value: RenderedItem | undefined
): RenderedItem | undefined => {
    if (old_value === new_value) {
        return new_value;
    }
    if (old_value) {
        markDropdownItemAsNotSelected(old_value);
    }
    if (new_value) {
        markDropdownItemAsSelected(new_value);
    }
    return new_value;
};

export const buildHasSelection = (host: InternalSelectionElement) => (): boolean =>
    host.selected_item !== undefined;

export const SelectionElement = define<InternalSelectionElement>({
    tag: TAG,
    placeholder_text: "",
    selected_item: {
        set: selectedItemSetter,
    },
    selected_item_element: {
        get: (host) => {
            const root = document.createElement("span");
            root.classList.add("lazybox-selected-value");
            root.setAttribute("aria-readonly", "true");
            const template = host.selected_item?.template ?? lit_html``;
            render(template, root);
            return root;
        },
    },
    selectItem: {
        get: buildSet,
    },
    clearSelection: {
        get: buildClear,
    },
    hasSelection: {
        get: buildHasSelection,
    },
    onSelection: undefined,
    getSelection: {
        get: (host) => (): RenderedItem | undefined => host.selected_item,
    },
    setFocus: {
        get: (host) => (): void => host.span_element.focus(),
    },
    span_element: {
        get: (host) => {
            const element = host.content().querySelector("[data-id=lazybox-selection]");
            if (!(element instanceof HTMLElement)) {
                throw Error("Could not find selection element");
            }
            return element;
        },
    },
    content: (host) => html`
        <span
            class="lazybox-selection lazybox-single"
            data-id="lazybox-selection"
            data-test="lazybox-selection"
            role="textbox"
            aria-readonly="true"
            tabindex="0"
            onkeyup="${onKeyUp}"
        >
            ${getContent(host)}${getClearSelectionButton(host)}
        </span>
    `,
});
