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

export const BOLD_TAG_NAME = "bold-item";

export type BoldElement = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
};

type InternalBoldElement = Readonly<BoldElement> & ToolbarButtonWithState;

export type HostElement = InternalBoldElement & HTMLElement;

const onClickApplyBold = (host: BoldElement): void => {
    host.toolbar_bus.bold();
};
export const renderBoldItem = (
    host: InternalBoldElement,
    gettext_provider: GetText,
): UpdateFunction<InternalBoldElement> => {
    const classes = getClass(host);

    return html`<button
        class="${classes}"
        onclick="${onClickApplyBold}"
        disabled="${host.is_disabled}"
        data-test="button-bold"
        title="${gettext_provider.gettext("Toggle bold style `Ctrl+b`")}"
    >
        <i class="fa-solid fa-bold" role="img"></i>
    </button>`;
};

export const connect = (host: InternalBoldElement): void => {
    host.toolbar_bus.setView({
        activateBold: (is_activated: boolean): void => {
            host.is_activated = is_activated;
        },
    });
};

define<InternalBoldElement>({
    tag: BOLD_TAG_NAME,
    is_activated: false,
    is_disabled: false,
    toolbar_bus: {
        value: (host: BoldElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) => renderBoldItem(host, host.gettext_provider),
});
