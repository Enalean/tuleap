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
import { gettext_provider } from "./gettext-provider";

export const BOLD_TAG_NAME = "bold-item";

export type BoldElement = {
    is_activated: boolean;
    toolbar_bus: ToolbarBus;
};

type InternalBoldElement = Readonly<BoldElement>;

const onClickApplyBold = (host: BoldElement): void => {
    host.toolbar_bus.bold();
};
export const renderBoldItem = (host: InternalBoldElement): UpdateFunction<InternalBoldElement> => {
    const classes = {
        "prose-mirror-button-activated": host.is_activated,
        "fa-solid": true,
        "fa-bold": true,
        "prose-mirror-toolbar-button-icon": true,
    };

    return html` <i
        class="${classes}"
        onclick="${onClickApplyBold}"
        data-test="button-bold"
        title="${gettext_provider.gettext("Toggle bold style `Ctrl+b`")}"
    ></i>`;
};

export default define<InternalBoldElement>({
    tag: BOLD_TAG_NAME,
    is_activated: false,
    toolbar_bus: (host: BoldElement, toolbar_bus: ToolbarBus) => toolbar_bus,
    render: renderBoldItem,
});
