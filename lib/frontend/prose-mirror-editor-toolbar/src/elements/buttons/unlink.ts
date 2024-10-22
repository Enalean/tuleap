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
import type { UpdateFunction } from "hybrids";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import type { ToolbarButtonWithState } from "../../helpers/class-getter";
import { getClass } from "../../helpers/class-getter";
import type { GetText } from "@tuleap/gettext";

export const TAG = "unlink-item";

export type UnlinkElement = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
};

export type InternalUnlinkElement = Readonly<UnlinkElement> & ToolbarButtonWithState;

export type HostElement = InternalUnlinkElement & HTMLElement;

const onClickRemoveLink = (host: InternalUnlinkElement): void => {
    host.toolbar_bus.unlink();
};

export const renderUnlinkElement = (
    host: InternalUnlinkElement,
    gettext_provider: GetText,
): UpdateFunction<InternalUnlinkElement> => {
    const classes = getClass(host);

    return html`
        <button
            class="${classes}"
            onclick="${onClickRemoveLink}"
            data-test="button-unlink"
            title="${gettext_provider.gettext("Remove link")}"
            disabled="${!host.is_activated || host.is_disabled}"
        >
            <i class="fa-solid fa-link-slash" role="img"></i>
        </button>
    `;
};

export const connect = (host: InternalUnlinkElement): void => {
    host.toolbar_bus.setView({
        activateUnlink: (is_activated: boolean): void => {
            host.is_activated = is_activated;
        },
    });
};

define<InternalUnlinkElement>({
    tag: TAG,
    is_activated: false,
    is_disabled: false,
    toolbar_bus: {
        value: (host: InternalUnlinkElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) => renderUnlinkElement(host, host.gettext_provider),
});
