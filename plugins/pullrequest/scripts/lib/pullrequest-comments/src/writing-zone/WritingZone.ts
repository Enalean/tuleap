/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import { initMentions } from "@tuleap/mention";
import { gettext_provider } from "../gettext-provider";
import type { ControlWritingZone } from "./WritingZoneController";
import type { WritingZonePresenter } from "./WritingZonePresenter";
import { getWritingZoneTemplate } from "./WritingZoneTemplate";

export const TAG = "tuleap-pullrequest-comment-writing-zone";

export type WritingZone = {
    controller: ControlWritingZone;
    presenter: WritingZonePresenter;
    readonly textarea: HTMLTextAreaElement;
    comment_content: string;
    render(): HTMLElement;
};

export type HostElement = WritingZone & HTMLElement;

export const isWritingZoneElement = (element: Element): element is HTMLElement & WritingZone => {
    return element.tagName.toLowerCase() === TAG;
};

export const getWritingZoneElement = (): HostElement => {
    const element = document.createElement(TAG);
    if (!isWritingZoneElement(element)) {
        throw new Error("Failed to create a WritingZone element.");
    }
    return element;
};

define<WritingZone>({
    tag: TAG,
    controller: {
        value: (host: WritingZone, controller) => controller,
        connect: (host) => {
            function onFocusIn(): void {
                host.controller.focusWritingZone(host);
            }

            function onFocusOut(): void {
                host.controller.blurWritingZone(host);
            }

            host.addEventListener("focusin", onFocusIn);
            host.addEventListener("focusout", onFocusOut);

            setTimeout(() => {
                if (host.controller.shouldFocusWritingZoneWhenConnected()) {
                    host.controller.focusWritingZone(host);
                }
            });

            return (): void => {
                host.controller.resetWritingZone(host);
                host.removeEventListener("focusin", onFocusIn);
                host.removeEventListener("focusout", onFocusOut);
            };
        },
    },
    presenter: (host, value) => value ?? host.controller.initWritingZone(),
    textarea: {
        value: (host: HostElement) => {
            const textarea_element = document.createElement("textarea");
            textarea_element.setAttribute("data-test", "writing-zone-textarea");
            textarea_element.classList.add(
                "pull-request-comment-writing-zone-textarea",
                "tlp-textarea",
            );
            textarea_element.rows = 10;
            textarea_element.placeholder = gettext_provider.gettext("Say something…");
            textarea_element.addEventListener("input", () => host.controller.onTextareaInput(host));

            initMentions(textarea_element);
            return textarea_element;
        },
        connect: (host) => {
            host.textarea.value = host.comment_content;
        },
    },
    comment_content: "",
    render: (host) => getWritingZoneTemplate(host, gettext_provider),
});
