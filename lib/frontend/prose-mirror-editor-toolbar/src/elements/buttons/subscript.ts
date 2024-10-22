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
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import type { ToolbarButtonWithState } from "../../helpers/class-getter";
import { getClass } from "../../helpers/class-getter";
import type { GetText } from "@tuleap/gettext";

export const SUBSCRIPT_TAG_NAME = "subscript-item";

export type SubscriptElement = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
};

type InternalSubscriptElement = Readonly<SubscriptElement> & ToolbarButtonWithState;

export type HostElement = InternalSubscriptElement & HTMLElement;

const onClickApplySubscript = (host: SubscriptElement): void => {
    host.toolbar_bus.subscript();
};
export const renderSubscriptItem = (
    host: InternalSubscriptElement,
    gettext_provider: GetText,
): UpdateFunction<InternalSubscriptElement> => {
    const classes = getClass(host);

    return html`<button
        class="${classes}"
        onclick="${onClickApplySubscript}"
        disabled="${host.is_disabled}"
        data-test="button-subscript"
        title="${gettext_provider.gettext("Apply subscript style on the selected text `Ctrl+,`")}"
    >
        <i class="fa-solid fa-subscript" role="img"></i>
    </button>`;
};

export const connect = (host: InternalSubscriptElement): void => {
    host.toolbar_bus.setView({
        activateSubscript: (is_activated: boolean): void => {
            host.is_activated = is_activated;
        },
    });
};

define<InternalSubscriptElement>({
    tag: SUBSCRIPT_TAG_NAME,
    is_activated: false,
    is_disabled: false,
    toolbar_bus: {
        value: (host: SubscriptElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) => renderSubscriptItem(host, host.gettext_provider),
});
