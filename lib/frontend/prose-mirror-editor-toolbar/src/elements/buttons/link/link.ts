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
import { createPopover } from "@tuleap/tlp-popovers";
import { renderLinkButtonElement } from "./link-button-template";
import type { ToolbarBus, LinkState } from "@tuleap/prose-mirror-editor";
import { renderLinkPopover } from "./link-popover-template";

export const TAG = "link-item";

export type Link = {
    toolbar_bus: ToolbarBus;
};

export type InternalLinkButtonElement = Readonly<Link> & {
    is_activated: boolean;
    is_disabled: boolean;
    button_element: HTMLButtonElement;
    popover_element: HTMLElement;
    link_href: string;
    link_title: string;
    render(): HTMLElement;
};

export type HostElement = InternalLinkButtonElement & HTMLElement;

function movePopoverToDocumentBody(host: InternalLinkButtonElement, doc: Document): void {
    // Move popover to document.body to avoid a toolbar position bug when the popover is closed.
    host.popover_element.remove();
    doc.body.appendChild(host.popover_element);
}

const movePopoverBackToHostElement = (host: InternalLinkButtonElement): void => {
    // Put back popover inside host element, so the component can continue working after being disconnected/reconnected.
    host.popover_element.remove();
    host.render().appendChild(host.popover_element);
};

type DisconnectFunction = () => void;
export const connect = (host: InternalLinkButtonElement, doc: Document): DisconnectFunction => {
    const popover = createPopover(host.button_element, host.popover_element, {
        trigger: "click",
        placement: "bottom-start",
    });

    host.toolbar_bus.setView({
        activateLink: (link_state: LinkState) => {
            host.is_activated = link_state.is_activated;
            host.link_href = link_state.link_href;
            host.link_title = link_state.link_title;
            host.is_disabled = link_state.is_disabled;
        },
    });

    movePopoverToDocumentBody(host, doc);

    return () => {
        popover.destroy();
        movePopoverBackToHostElement(host);
    };
};

define<InternalLinkButtonElement>({
    tag: TAG,
    is_activated: false,
    is_disabled: true,
    link_href: "",
    link_title: "",
    button_element: (host: InternalLinkButtonElement) => {
        const button_element = host.render().querySelector("[data-role=popover-trigger]");
        if (!(button_element instanceof HTMLButtonElement)) {
            throw new Error("Unable to find button_element.");
        }
        return button_element;
    },
    popover_element: (host: InternalLinkButtonElement) => {
        const popover_element = host.render().querySelector("[data-role=popover]");
        if (!(popover_element instanceof HTMLElement)) {
            throw new Error("Unable to find popover_element.");
        }

        return popover_element;
    },
    toolbar_bus: {
        value: (host: InternalLinkButtonElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect: (host) => connect(host, document),
    },
    render: (host) => html`${renderLinkButtonElement(host)}${renderLinkPopover(host)}`,
});
