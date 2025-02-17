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

export const BULLET_LIST_TAG_NAME = "bullet-list-item";

export type BulletListElement = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
    is_toolbar_disabled: boolean;
};

type InternalBulletListElement = Readonly<BulletListElement> & ToolbarButtonWithState;

export type HostElement = InternalBulletListElement & HTMLElement;

const onClickApplyBulletList = (host: BulletListElement): void => {
    host.toolbar_bus.bulletList();
};
export const renderBulletListItem = (
    host: InternalBulletListElement,
    gettext_provider: GetText,
): UpdateFunction<InternalBulletListElement> => {
    const classes = getClass(host);

    return html`<button
        class="${classes}"
        onclick="${onClickApplyBulletList}"
        data-test="button-bullet"
        title="${gettext_provider.gettext("Wrap in bullet list `Shift+Ctrl+8`")}"
        disabled="${host.is_disabled}"
    >
        <i class="fa-solid fa-list" role="img"></i>
    </button>`;
};

export const connect = (host: InternalBulletListElement): void => {
    host.toolbar_bus.setView({
        activateBulletList: (list_state: ListState): void => {
            if (host.is_toolbar_disabled) {
                return;
            }
            host.is_activated = list_state.is_activated;
            host.is_disabled = list_state.is_disabled;
        },
    });
};

define<InternalBulletListElement>({
    tag: BULLET_LIST_TAG_NAME,
    is_activated: false,
    is_disabled: true,
    is_toolbar_disabled: true,
    toolbar_bus: {
        value: (host: BulletListElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) => renderBulletListItem(host, host.gettext_provider),
});
