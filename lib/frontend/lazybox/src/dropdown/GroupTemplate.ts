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

import type { UpdateFunction } from "hybrids";
import { html } from "hybrids";
import type { DropdownElement } from "./DropdownElement";
import type { GroupOfItems, LazyboxItem } from "../GroupCollection";
import { isEnterKey } from "../helpers/keys-helper";
import { onArrowKeyDown, onArrowKeyUp } from "./DropdownElement";

export const getItemTemplate = (
    host: DropdownElement,
    item: LazyboxItem
): UpdateFunction<DropdownElement> => {
    if (item.is_disabled) {
        return html`<li
            role="option"
            class="lazybox-dropdown-option-value-disabled"
            data-test="lazybox-item"
        >
            ${host.templating_callback(html, item)}
        </li>`;
    }

    const onPointerUp = (host: DropdownElement): void => {
        host.selection.selectItem(item);
        host.open = false;
    };

    const onKeyUp = (host: DropdownElement & HTMLElement, event: KeyboardEvent): void => {
        if (isEnterKey(event)) {
            event.stopPropagation();
            host.selection.selectItem(item);
            host.open = false;
            return;
        }
        onArrowKeyUp(host, event);
    };

    const aria_value = String(host.selection.isSelected(item));
    return html`<li
        role="option"
        tabindex="0"
        aria-selected="${aria_value}"
        class="lazybox-dropdown-option-value"
        data-test="lazybox-item"
        data-navigation="lazybox-item"
        onpointerup="${onPointerUp}"
        onkeyup="${onKeyUp}"
        onkeydown="${onArrowKeyDown}"
    >
        ${host.templating_callback(html, item)}
    </li>`;
};

const createEmptyDropdownState = (dropdown_message: string): UpdateFunction<DropdownElement> =>
    html`<li
        class="lazybox-empty-dropdown-state"
        role="alert"
        aria-live="assertive"
        data-test="lazybox-empty-state"
    >
        ${dropdown_message}
    </li>`;

const getGroupLabel = (group: GroupOfItems): UpdateFunction<DropdownElement> => {
    if (group.is_loading) {
        return html`<strong class="lazybox-group-label">
            ${group.label}
            <i
                class="fa-solid fa-spin fa-circle-notch lazybox-loading-group-spinner"
                data-test="lazybox-loading-group-spinner"
            ></i>
        </strong>`;
    }

    return html`<strong class="lazybox-group-label">${group.label}</strong>`;
};

const getGroupFooter = (group: GroupOfItems): UpdateFunction<DropdownElement> =>
    group.footer_message !== "" && group.items.length > 0
        ? html`<p class="lazybox-group-footer" data-test="lazybox-group-footer">
              ${group.footer_message}
          </p>`
        : html``;

const getGroupTemplate = (
    host: DropdownElement,
    group: GroupOfItems
): UpdateFunction<DropdownElement> => {
    const item_templates =
        group.items.length === 0
            ? [createEmptyDropdownState(group.empty_message)]
            : group.items.map((item) => getItemTemplate(host, item));

    return html`<li class="lazybox-item-group">
        ${getGroupLabel(group)}
        <ul role="group" aria-label="${group.label}" class="lazybox-dropdown-option-group">
            ${item_templates}
        </ul>
        ${getGroupFooter(group)}
    </li>`;
};

export const getAllGroupsTemplate = (host: DropdownElement): UpdateFunction<DropdownElement> =>
    html`${host.groups.map((group) => getGroupTemplate(host, group))}`;
