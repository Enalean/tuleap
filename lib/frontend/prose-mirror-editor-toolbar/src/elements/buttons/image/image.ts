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
import type { ToolbarBus, ImageState } from "@tuleap/prose-mirror-editor";
import { connectPopover } from "../common/connect-popover";
import type { PopoverHost } from "../common/connect-popover";
import { renderImageButton } from "./image-button-template";
import { renderImagePopover } from "./image-popover-template";
import type { GetText } from "@tuleap/gettext";
import type { ToolbarButtonWithState } from "../../../helpers/class-getter";

export const TAG = "image-item";

export type ImageButton = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
};

export type InternalImageButton = Readonly<ImageButton> &
    PopoverHost &
    ToolbarButtonWithState & {
        image_src: string;
        image_title: string;
    };

export type HostElement = InternalImageButton & HTMLElement;

export const connect = (host: InternalImageButton): void => {
    host.toolbar_bus.setView({
        activateImage: (image_state: ImageState) => {
            host.is_activated = image_state.is_activated;
            host.is_disabled = image_state.is_disabled;
            host.image_src = image_state.image_src;
            host.image_title = image_state.image_title;
        },
    });
};

define<InternalImageButton>({
    tag: TAG,
    is_activated: false,
    is_disabled: true,
    image_src: "",
    image_title: "",
    popover_instance: (host, popover_instance) => popover_instance,
    button_element: (host: InternalImageButton) => {
        const button_element = host.render().querySelector("[data-role=popover-trigger]");
        if (!(button_element instanceof HTMLButtonElement)) {
            throw new Error("Unable to find button_element.");
        }
        return button_element;
    },
    popover_element: {
        value: (host: InternalImageButton) => {
            const popover_element = host.render().querySelector("[data-role=popover]");
            if (!(popover_element instanceof HTMLElement)) {
                throw new Error("Unable to find popover_element.");
            }

            return popover_element;
        },
        connect: (host) => connectPopover(host, document),
    },
    toolbar_bus: {
        value: (host: InternalImageButton, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host): UpdateFunction<InternalImageButton> =>
        html`${renderImageButton(host, host.gettext_provider)}${renderImagePopover(
            host,
            host.gettext_provider,
        )}`,
});
