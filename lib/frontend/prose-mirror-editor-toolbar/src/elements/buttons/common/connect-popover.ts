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

import { createPopover, EVENT_TLP_POPOVER_HIDDEN } from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";

export type PopoverHost = {
    button_element: HTMLButtonElement;
    popover_element: HTMLElement;
    popover_instance: Popover;
    toolbar_bus: ToolbarBus;
    render(): HTMLElement;
};

type DisconnectFunction = () => void;

function movePopoverToDocumentBody(host: PopoverHost, doc: Document): void {
    // Move popover to document.body to avoid a toolbar position bug when the popover is closed.
    doc.body.appendChild(host.popover_element);
}

const movePopoverBackToHostElement = (host: PopoverHost, popover_element: HTMLElement): void => {
    // Put back popover inside host element, so the component can continue working after being disconnected/reconnected.
    host.render().appendChild(popover_element);
};

export const connectPopover = (host: PopoverHost, doc: Document): DisconnectFunction => {
    host.popover_instance = createPopover(host.button_element, host.popover_element, {
        trigger: "click",
        placement: "bottom-start",
    });

    // Keep track of the HTMLElement so we'll be able to put it back in the toolbar when it is disconnected
    const popover_element = host.popover_element;

    const onPopoverHidden = (): void => {
        host.toolbar_bus.focusEditor();
    };

    popover_element.addEventListener(EVENT_TLP_POPOVER_HIDDEN, onPopoverHidden);

    movePopoverToDocumentBody(host, doc);

    return () => {
        host.popover_instance.destroy();
        popover_element.removeEventListener(EVENT_TLP_POPOVER_HIDDEN, onPopoverHidden);
        movePopoverBackToHostElement(host, popover_element);
    };
};
