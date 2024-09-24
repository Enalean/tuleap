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
import type { Popover } from "@tuleap/tlp-popovers";
import { createPopover } from "@tuleap/tlp-popovers";
import { renderLinkPopover } from "./LinkPopoverTemplate";
import type { RenderButtons } from "./LinkPopoverButtonsRenderers";
import type { RenderEditionForm } from "./LinkPopoverEditionFormRenderers";

export const TAG = "tuleap-prose-mirror-link-popover-element";

export type LinkPopoverElement = {
    popover_anchor: HTMLElement;
    buttons_renderer: RenderButtons;
    edition_form_renderer: RenderEditionForm;
};

export type InternalLinkPopoverElement = Readonly<LinkPopoverElement> & {
    popover_element: HTMLElement;
    popover_instance: Popover | null;
    is_in_edition_mode: boolean;
    render(): HTMLElement;
};

export type HostElement = InternalLinkPopoverElement & HTMLElement;

const createPopoverInstance = (host: InternalLinkPopoverElement): void => {
    host.popover_instance = createPopover(host.popover_anchor, host.popover_element, {
        placement: "top",
        trigger: "click",
    });
    host.popover_instance.show();
};

type DisconnectFunction = () => void;
export const connect = (host: HostElement): DisconnectFunction => {
    createPopoverInstance(host);

    return () => {
        host.popover_instance?.destroy();
    };
};

export const observeEditionMode = (
    host: InternalLinkPopoverElement,
    is_in_edition_mode: boolean,
): void => {
    if (is_in_edition_mode) {
        host.popover_instance?.destroy();
        return;
    }

    createPopoverInstance(host);
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
    popover_instance: (host, popover_instance) => popover_instance,
    buttons_renderer: (host, buttons_renderer) => buttons_renderer,
    edition_form_renderer: (host, edition_form_renderer) => edition_form_renderer,
    is_in_edition_mode: {
        value: false,
        observe: observeEditionMode,
    },
    render: {
        shadow: false,
        value: renderLinkPopover,
    },
});
