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

export const ITALIC_TAG_NAME = "italic-item";

export type ItalicElement = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
};

type InternalItalicElement = Readonly<ItalicElement> & ToolbarButtonWithState;

export type HostElement = InternalItalicElement & HTMLElement;
const onClickApplyItalic = (host: ItalicElement): void => {
    host.toolbar_bus.italic();
};
export const renderItalicItem = (
    host: InternalItalicElement,
    gettext_provider: GetText,
): UpdateFunction<InternalItalicElement> => {
    const classes = getClass(host);

    return html`<button
        class="${classes}"
        onclick="${onClickApplyItalic}"
        data-test="button-italic"
        disabled="${host.is_disabled}"
        title="${gettext_provider.gettext("Toggle italic style `Ctrl+i`")}"
    >
        <i class="fa-solid fa-italic" role="img"></i>
    </button>`;
};

export const connect = (host: InternalItalicElement): void => {
    host.toolbar_bus.setView({
        activateItalic: (is_activated: boolean): void => {
            host.is_activated = is_activated;
        },
    });
};

export default define<InternalItalicElement>({
    tag: ITALIC_TAG_NAME,
    is_activated: false,
    is_disabled: false,
    toolbar_bus: {
        value: (host: ItalicElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) => renderItalicItem(host, host.gettext_provider),
});
