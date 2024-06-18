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

import { define, dispatch, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { LazyboxTemplatingCallback } from "../Options";
import { getAllGroupsTemplate } from "./GroupTemplate";
import type { GroupCollection } from "../GroupCollection";
import { isArrowDown, isArrowUp } from "../helpers/keys-helper";
import { moveFocus } from "@tuleap/focus-navigation";
import type { SearchInput } from "../SearchInput";
import type { HandleSelection } from "../selection/HandleSelection";

export const TAG = "tuleap-lazybox-dropdown";

export type DropdownElement = {
    open: boolean;
    multiple_selection: boolean;
    groups: GroupCollection;
    has_new_item: boolean;
    new_item_button_label: string;
    templating_callback: LazyboxTemplatingCallback;
    search_input: SearchInput & HTMLElement;
    selection: HandleSelection;
};
type InternalDropdownElement = Readonly<DropdownElement> & {
    render(): HTMLElement;
};
export type HostElement = InternalDropdownElement & HTMLElement;

export const observeOpen = (
    host: HostElement,
    new_value: boolean,
    old_value: boolean | undefined,
): void => {
    if (old_value === undefined) {
        // Do not focus at initial render
        return;
    }
    if (new_value) {
        host.render();
        dispatch(host, "open");
        return;
    }
    dispatch(host, "close");
};

const onClickOnNewItemButton = (host: HostElement): void => {
    dispatch(host, "click-create-item");
};

export const onArrowKeyUp = (host: HTMLElement, event: KeyboardEvent): void => {
    const getParent = (): HTMLElement => host;

    if (isArrowDown(event)) {
        moveFocus(host.ownerDocument, "down", getParent);
        return;
    }
    if (isArrowUp(event)) {
        moveFocus(host.ownerDocument, "up", getParent);
    }
};

export const onArrowKeyDown = (host: unknown, event: KeyboardEvent): void => {
    if (isArrowDown(event) || isArrowUp(event)) {
        event.preventDefault();
    }
};

export const renderDropdownElement = (
    host: InternalDropdownElement,
): UpdateFunction<InternalDropdownElement> => {
    const search_section = !host.multiple_selection ? html`${host.search_input}` : html``;
    const new_item_button = host.has_new_item
        ? html`<button
              type="button"
              class="lazybox-new-item-button"
              onclick="${onClickOnNewItemButton}"
              data-test="new-item-button"
              data-navigation="lazybox-item"
              onkeyup="${onArrowKeyUp}"
              onkeydown="${onArrowKeyDown}"
          >
              ${host.new_item_button_label}
          </button>`
        : html``;

    return html`${search_section}${new_item_button}
        <ul
            class="lazybox-dropdown-values-list"
            role="listbox"
            aria-expanded="true"
            aria-hidden="false"
        >
            ${getAllGroupsTemplate(host)}
        </ul>`;
};

export const DropdownElement = define<InternalDropdownElement>({
    tag: TAG,
    open: { observe: observeOpen, value: false, reflect: true },
    multiple_selection: false,
    groups: (host, new_value) => new_value ?? [],
    has_new_item: false,
    new_item_button_label: "",
    templating_callback: (host, value) => value,
    search_input: (host, value) => value,
    selection: (host, value) => value,
    render: renderDropdownElement,
});
