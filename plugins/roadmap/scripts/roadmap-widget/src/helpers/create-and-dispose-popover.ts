/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import type { Ref } from "vue";
import { watch, onScopeDispose } from "vue";
import type { Popover } from "@tuleap/tlp-popovers/src";

export function usePopover(
    container_element: Ref<HTMLElement | undefined>,
    popover_element_id: Ref<string>,
): void {
    let current_popover: Popover | null = null;

    function cleanup(): void {
        if (current_popover === null) {
            return;
        }
        current_popover.destroy();
        current_popover = null;
    }

    watch([container_element, popover_element_id], ([container, popover_id]) => {
        cleanup();

        if (popover_id && container) {
            const popover_element = document.getElementById(popover_id);
            if (!(popover_element instanceof HTMLElement)) {
                return;
            }

            current_popover = createPopover(container, popover_element, {
                placement: "right-start",
                middleware: {
                    flip: {
                        fallbackPlacements: ["left-start", "top"],
                    },
                    offset: {
                        alignmentAxis: 0,
                    },
                },
            });
        }
    });

    onScopeDispose(cleanup);
}
