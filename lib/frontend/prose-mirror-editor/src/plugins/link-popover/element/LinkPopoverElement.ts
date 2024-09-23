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

import { define } from "hybrids";
import { createPopover } from "@tuleap/tlp-popovers";
import { renderLinkPopover } from "./LinkPopoverTemplate";
import type { RenderButtons } from "./LinkPopoverButtonsRenderers";

export const TAG = "tuleap-prose-mirror-link-popover-element";

export type LinkPopoverElement = {
    popover_anchor: HTMLElement;
    buttons_renderer: RenderButtons;
};

export type InternalLinkPopoverElement = Readonly<LinkPopoverElement> & {
    popover_element: HTMLElement;
    render(): HTMLElement;
};

export type HostElement = InternalLinkPopoverElement & HTMLElement;

type DisconnectFunction = () => void;
export const connect = (host: HostElement): DisconnectFunction => {
    const popover_instance = createPopover(host.popover_anchor, host.popover_element, {
        placement: "top",
        trigger: "click",
    });
    popover_instance.show();

    return () => {
        popover_instance.destroy();
    };
};

define<InternalLinkPopoverElement>({
    tag: TAG,
    popover_anchor: (host, popover_anchor) => popover_anchor,
    popover_element: {
        value: (host: InternalLinkPopoverElement): HTMLElement => {
            const popover = host.render().querySelector<HTMLElement>("[data-role=popover]");
            if (!popover) {
                throw new Error("Unable to retrieve the popover element :(");
            }

            return popover;
        },
        connect,
    },
    buttons_renderer: (host, buttons_renderer) => buttons_renderer,
    render: {
        shadow: false,
        value: renderLinkPopover,
    },
});
