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
import { gettext_provider } from "../../gettext-provider";
import { getClass } from "../../helpers/class-getter";

export const EMBEDDED_TAG_NAME = "embedded-item";

export type EmbeddedElement = {
    toolbar_bus: ToolbarBus;
};

type InternalEmbeddedElement = Readonly<EmbeddedElement> & {
    is_activated: boolean;
};

export type HostElement = InternalEmbeddedElement & HTMLElement;
const onClickApplyEmbedded = (host: EmbeddedElement): void => {
    host.toolbar_bus.embedded();
};
export const renderEmbeddedItem = (
    host: InternalEmbeddedElement,
): UpdateFunction<InternalEmbeddedElement> => {
    const classes = getClass(host.is_activated);

    return html`<button
        class="${classes}"
        onclick="${onClickApplyEmbedded}"
        data-test="button-embedded"
        title="${gettext_provider.gettext("Toggle embedded style `Ctrl+i`")}"
    >
        <i class="fa-solid fa-italic" role="img"></i>
    </button>`;
};

export const connect = (host: InternalEmbeddedElement): void => {
    host.toolbar_bus.setView({
        activateEmbedded: (is_activated: boolean): void => {
            host.is_activated = is_activated;
        },
    });
};

export default define<InternalEmbeddedElement>({
    tag: EMBEDDED_TAG_NAME,
    is_activated: false,
    toolbar_bus: {
        value: (host: EmbeddedElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    render: renderEmbeddedItem,
});
