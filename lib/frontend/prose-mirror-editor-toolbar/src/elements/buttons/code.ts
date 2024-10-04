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

export const CODE_TAG_NAME = "code-item";

export type CodeElement = {
    toolbar_bus: ToolbarBus;
};

type InternalCodeElement = Readonly<CodeElement> & {
    is_activated: boolean;
};

export type HostElement = InternalCodeElement & HTMLElement;
const onClickApplyCode = (host: CodeElement): void => {
    host.toolbar_bus.code();
};

export const connect = (host: InternalCodeElement): void => {
    host.toolbar_bus.setView({
        activateCode: (is_activated: boolean): void => {
            host.is_activated = is_activated;
        },
    });
};
export const renderCodeItem = (host: InternalCodeElement): UpdateFunction<InternalCodeElement> => {
    const classes = getClass(host.is_activated);

    return html`<button
        class="${classes}"
        onclick="${onClickApplyCode}"
        data-test="button-code"
        title="${gettext_provider.gettext("Toggle code Ctrl+`")}"
    >
        <i class="fa-solid fa-code" role="img"></i>
    </button>`;
};

export default define<InternalCodeElement>({
    tag: CODE_TAG_NAME,
    is_activated: false,
    toolbar_bus: {
        value: (host: CodeElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    render: renderCodeItem,
});
