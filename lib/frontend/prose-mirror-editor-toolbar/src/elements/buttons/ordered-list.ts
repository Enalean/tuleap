/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import { define, html, type UpdateFunction } from "hybrids";
import type { ToolbarBus, ListState } from "@tuleap/prose-mirror-editor";
import type { ToolbarButtonWithState } from "../../helpers/class-getter";
import { getClass } from "../../helpers/class-getter";
import type { GetText } from "@tuleap/gettext";

export const ORDERED_LIST_TAG_NAME = "ordered-list-item";

export type OrderedListElement = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
    is_toolbar_disabled: boolean;
};

type InternalOrderedListElement = Readonly<OrderedListElement> & ToolbarButtonWithState;

export type HostElement = InternalOrderedListElement & HTMLElement;

const onClickApplyOrderedList = (host: OrderedListElement): void => {
    host.toolbar_bus.orderedList();
};
export const renderOrderedListItem = (
    host: InternalOrderedListElement,
    gettext_provider: GetText,
): UpdateFunction<InternalOrderedListElement> => {
    const classes = getClass(host);

    return html`<button
        class="${classes}"
        onclick="${onClickApplyOrderedList}"
        data-test="button-ordered"
        disabled="${host.is_disabled}"
        title="${gettext_provider.gettext("Wrap in ordered list `Shift+Ctrl+9`")}"
    >
        <i class="fa-solid fa-list-ol" role="img"></i>
    </button>`;
};

export const connect = (host: InternalOrderedListElement): void => {
    host.toolbar_bus.setView({
        activateOrderedList: (list_state: ListState): void => {
            if (host.is_toolbar_disabled) {
                return;
            }
            host.is_activated = list_state.is_activated;
            host.is_disabled = list_state.is_disabled;
        },
    });
};

define<InternalOrderedListElement>({
    tag: ORDERED_LIST_TAG_NAME,
    is_activated: false,
    is_disabled: true,
    is_toolbar_disabled: true,
    toolbar_bus: {
        value: (host: OrderedListElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) => renderOrderedListItem(host, host.gettext_provider),
});
