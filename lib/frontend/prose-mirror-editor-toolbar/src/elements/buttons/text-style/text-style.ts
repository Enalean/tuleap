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
import type { ToolbarBus, Heading } from "@tuleap/prose-mirror-editor";
import { renderHeadingsOptions } from "./heading-option-template";
import { renderPlainTextOption } from "./plain-text-option-template";
import { renderStylesOption } from "./styles-option-template";

export const TAG = "text-style-item";

export type HeadingsItem = {
    toolbar_bus: ToolbarBus;
};

export type InternalHeadingsItem = Readonly<HeadingsItem> & {
    current_heading: Heading | null;
    is_plain_text_activated: boolean;
};

export type HostElement = InternalHeadingsItem & HTMLElement;

export const connect = (host: InternalHeadingsItem): void => {
    host.toolbar_bus.setView({
        activateHeading: (heading: Heading | null) => {
            host.current_heading = heading;
        },
        activatePlainText: (is_activated: boolean) => {
            host.is_plain_text_activated = is_activated;
        },
    });
};

define<InternalHeadingsItem>({
    tag: TAG,
    current_heading: null,
    is_plain_text_activated: false,
    toolbar_bus: {
        value: (host: InternalHeadingsItem, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    render: (host: InternalHeadingsItem): UpdateFunction<InternalHeadingsItem> => html`
        <select class="tlp-select tlp-select-small tlp-select-adjusted">
            ${renderStylesOption(host)} ${renderPlainTextOption(host)}
            ${renderHeadingsOptions(host)}
        </select>
    `,
});
