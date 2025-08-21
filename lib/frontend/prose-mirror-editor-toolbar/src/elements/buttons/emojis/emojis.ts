/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { UpdateFunction } from "hybrids";
import { define, html } from "hybrids";
import type { ToolbarBus, EmojiState } from "@tuleap/prose-mirror-editor";
import type { GetText } from "@tuleap/gettext";
import type { PopoverHost } from "../common/connect-popover";
import { connectPopover } from "../common/connect-popover";
import type { ToolbarButtonWithState } from "../../../helpers/class-getter";
import { renderEmojiButton } from "./emoji-button-template";
import { renderEmojiPopover } from "./emoji-popover-template";

export const TAG = "emoji-item";

export type EmojiButton = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
};

export type InternalEmojiButton = Readonly<EmojiButton> &
    PopoverHost &
    ToolbarButtonWithState & {
        emoji_string: string;
    };

export type HostElement = InternalEmojiButton & HTMLElement;

export const connect = (host: InternalEmojiButton): void => {
    host.toolbar_bus.setView({
        activateEmoji: (emoji_state: EmojiState) => {
            host.is_activated = emoji_state.is_activated;
            host.is_disabled = emoji_state.is_disabled;
            host.emoji_string = emoji_state.emoji_string;
        },
        toggleToolbarMenu: (menu: string) => {
            if (menu !== "emoji" || !host.popover_instance) {
                return;
            }

            host.popover_instance.show();
        },
    });
};

define<InternalEmojiButton>({
    tag: TAG,
    is_activated: false,
    is_disabled: true,
    emoji_string: "",
    popover_instance: (host, popover_instance) => popover_instance,
    button_element: (host: InternalEmojiButton) => {
        const button_element = host
            .render()
            .querySelector<HTMLButtonElement>("[data-role=popover-trigger]");
        if (button_element === null) {
            throw new Error("Unable to find button_element.");
        }
        return button_element;
    },
    popover_element: {
        value: (host: InternalEmojiButton) => {
            const popover_element = host.render().querySelector<HTMLElement>("[data-role=popover]");
            if (popover_element === null) {
                throw new Error("Unable to find popover_element.");
            }
            return popover_element;
        },
        connect: (host) => connectPopover(host, document),
    },
    toolbar_bus: {
        value: (host: InternalEmojiButton, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host): UpdateFunction<InternalEmojiButton> =>
        html`${renderEmojiButton(host, host.gettext_provider)}${renderEmojiPopover(
            host,
            host.gettext_provider,
        )}`,
});
