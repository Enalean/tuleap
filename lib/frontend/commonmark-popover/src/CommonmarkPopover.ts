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
import { selectOrThrow } from "@tuleap/dom";
import { gettext_provider } from "./gettext-provider";
import type { ControlCommonmarkPopover } from "./CommonmarkPopoverController";
import { CommonmarkPopoverController } from "./CommonmarkPopoverController";
import {
    getPopoverTemplate,
    POPOVER_ANCHOR_CLASSNAME,
    POPOVER_CLASSNAME,
    POPOVER_TRIGGER_CLASSNAME,
} from "./CommonmarkPopoverTemplate";

export const TAG = "tuleap-commonmark-popover";

export type InternalCommonmarkPopover = {
    is_open: boolean;
    controller: ControlCommonmarkPopover;
    popover_trigger_element: HTMLElement;
    popover_anchor_element: HTMLElement;
    popover_content_element: HTMLElement;
    content: () => HTMLElement;
};

export type HostElement = InternalCommonmarkPopover & HTMLElement;

export const CommonmarkPopover = define<InternalCommonmarkPopover>({
    tag: TAG,
    is_open: false,
    popover_trigger_element: {
        get: (host) => selectOrThrow(host, `.${POPOVER_TRIGGER_CLASSNAME}`),
    },
    popover_anchor_element: {
        get: (host) => selectOrThrow(host, `.${POPOVER_ANCHOR_CLASSNAME}`),
    },
    popover_content_element: {
        get: (host) => selectOrThrow(host, `.${POPOVER_CLASSNAME}`),
    },
    controller: {
        get: (host: InternalCommonmarkPopover, controller: ControlCommonmarkPopover | undefined) =>
            controller ?? CommonmarkPopoverController(),
        set: (host: InternalCommonmarkPopover, controller: ControlCommonmarkPopover) =>
            // set is needed for testing purpose
            controller,
        connect: (host) => {
            setTimeout(() => host.controller.initPopover(host));

            return (): void => {
                host.controller.destroyPopover();
            };
        },
    },
    content: (host) => getPopoverTemplate(host, gettext_provider),
});
