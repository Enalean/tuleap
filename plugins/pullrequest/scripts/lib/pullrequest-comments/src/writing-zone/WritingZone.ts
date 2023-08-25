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
import { gettext_provider } from "../gettext-provider";
import type { ElementContainingAWritingZone } from "../types";
import type { ControlWritingZone } from "./WritingZoneController";
import type { WritingZonePresenter } from "./WritingZonePresenter";
import { getWritingZoneTemplate } from "./WritingZoneTemplate";

export const TAG = "tuleap-pullrequest-comment-writing-zone";

export type InternalWritingZone = {
    controller: ControlWritingZone;
    presenter: WritingZonePresenter;
    textarea: HTMLTextAreaElement;
    content: () => HTMLElement;
};

export type HostElement = InternalWritingZone & HTMLElement;

export const isWritingZoneElement = (
    element: Element
): element is HTMLElement & InternalWritingZone => {
    return element.tagName.toLowerCase() === TAG;
};

export const getWritingZoneElement = <ElementType>(
    host: ElementContainingAWritingZone<ElementType>
): HTMLElement & InternalWritingZone => {
    const element = document.createElement(TAG);
    if (!isWritingZoneElement(element)) {
        throw new Error("Failed to create a WritingZone element.");
    }

    element.controller = host.writing_zone_controller;
    element.addEventListener("writing-zone-input", (event: Event) => {
        if (!(event instanceof CustomEvent)) {
            return;
        }

        host.controller.handleWritingZoneContentChange(host, event.detail.content);
    });

    return element;
};

export const WritingZoneElement = define<InternalWritingZone>({
    tag: TAG,
    controller: {
        set: (host: InternalWritingZone, controller) => {
            controller.initWritingZone(host);
            return controller;
        },
        connect: (host) => {
            const onClickInDocumentHandler = (event: MouseEvent): void => {
                if (!event.composedPath().includes(host)) {
                    host.controller.blurWritingZone(host);
                    return;
                }

                host.controller.focusWritingZone(host);
            };

            host.controller.getDocument().addEventListener("click", onClickInDocumentHandler);

            setTimeout(() => {
                if (host.controller.shouldFocusWritingZoneWhenConnected()) {
                    host.controller.focusWritingZone(host);
                }
            });

            return (): void => {
                host.controller.resetWritingZone(host);
                host.controller
                    .getDocument()
                    .removeEventListener("click", onClickInDocumentHandler);
            };
        },
    },
    presenter: undefined,
    textarea: {
        get: (host) => {
            const textarea_element = document.createElement("textarea");
            textarea_element.setAttribute("data-test", "writing-zone-textarea");
            textarea_element.setAttribute(
                "class",
                "pull-request-comment-writing-zone-textarea tlp-textarea"
            );
            textarea_element.setAttribute("rows", "10");
            textarea_element.setAttribute(
                "placeholder",
                gettext_provider.gettext("Say something…")
            );
            textarea_element.addEventListener("input", () => host.controller.onTextareaInput(host));

            return textarea_element;
        },
        connect: (host) => {
            host.textarea.value = host.presenter.initial_content;
        },
    },
    content: (host) => getWritingZoneTemplate(host, gettext_provider),
});
