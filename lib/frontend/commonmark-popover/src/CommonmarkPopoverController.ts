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

import { createPopover } from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers";
import type { HostElement, InternalCommonmarkPopover } from "./CommonmarkPopover";

export type ControlCommonmarkPopover = {
    initPopover(host: HostElement): void;
    destroyPopover(): void;
    onPopoverShown(host: InternalCommonmarkPopover): void;
    onPopoverHidden(host: InternalCommonmarkPopover): void;
};

export const CommonmarkPopoverController = (): ControlCommonmarkPopover => {
    let popover_instance: Popover;

    return {
        initPopover: (host: HostElement): void => {
            if (popover_instance) {
                return;
            }

            popover_instance = createPopover(
                host.popover_trigger_element,
                host.popover_content_element,
                {
                    anchor: host.popover_anchor_element,
                    placement: "right-start",
                    trigger: "click",
                },
            );
        },

        destroyPopover: (): void => {
            popover_instance.destroy();
        },

        onPopoverShown: (host: InternalCommonmarkPopover): void => {
            host.is_open = true;
        },

        onPopoverHidden: (host: InternalCommonmarkPopover): void => {
            host.is_open = false;
        },
    };
};
