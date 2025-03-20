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
import type { ToolbarBus, LinkState } from "@tuleap/prose-mirror-editor";
import type { PopoverHost } from "../common/connect-popover";
import { connectPopover } from "../common/connect-popover";
import { renderLinkButtonElement } from "./link-button-template";
import { renderLinkPopover } from "./link-popover-template";
import type { GetText } from "@tuleap/gettext";
import type { ToolbarButtonWithState } from "../../../helpers/class-getter";

export const TAG = "link-item";

export type Link = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
    is_toolbar_disabled: boolean;
};

export type InternalLinkButtonElement = Readonly<Link> &
    PopoverHost &
    ToolbarButtonWithState & {
        link_href: string;
        link_title: string;
    };

export type HostElement = InternalLinkButtonElement & HTMLElement;

export const connect = (host: InternalLinkButtonElement): void => {
    host.toolbar_bus.setView({
        activateLink: (link_state: LinkState) => {
            if (host.is_toolbar_disabled) {
                return;
            }
            host.is_activated = link_state.is_activated;
            host.link_href = link_state.link_href;
            host.link_title = link_state.link_title;
            host.is_disabled = link_state.is_disabled;
        },
        toggleToolbarMenu: (menu: string) => {
            if (menu !== "link" || !host.popover_instance) {
                return;
            }

            host.popover_instance.show();
        },
    });
};

define<InternalLinkButtonElement>({
    tag: TAG,
    is_activated: false,
    is_disabled: true,
    is_toolbar_disabled: true,
    link_href: "",
    link_title: "",
    popover_instance: (host, popover_instance) => popover_instance,
    button_element: (host: InternalLinkButtonElement) => {
        const button_element = host.render().querySelector("[data-role=popover-trigger]");
        if (!(button_element instanceof HTMLButtonElement)) {
            throw new Error("Unable to find button_element.");
        }
        return button_element;
    },
    popover_element: {
        value: (host: InternalLinkButtonElement) => {
            const popover_element = host.render().querySelector("[data-role=popover]");
            if (!(popover_element instanceof HTMLElement)) {
                throw new Error("Unable to find popover_element.");
            }

            return popover_element;
        },
        connect: (host: InternalLinkButtonElement) => connectPopover(host, document),
    },
    toolbar_bus: {
        value: (host: InternalLinkButtonElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) =>
        html`${renderLinkButtonElement(host, host.gettext_provider)}${renderLinkPopover(
            host,
            host.gettext_provider,
        )}`,
});
