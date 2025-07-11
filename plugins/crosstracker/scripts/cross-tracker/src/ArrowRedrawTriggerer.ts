/*
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

import type { Emitter } from "mitt";
import type { Events } from "./helpers/widget-events";
import { SELECTABLE_TABLE_RESIZED_EVENT } from "./helpers/widget-events";

export type ArrowRedrawTriggerer = {
    listenToSelectableTableResize(element: HTMLElement): void;
    removeListener(element: HTMLElement): void;
};

export const ArrowRedrawTriggerer = (emitter: Emitter<Events>): ArrowRedrawTriggerer => {
    const resize_observer = new ResizeObserver(() => {
        emitter.emit(SELECTABLE_TABLE_RESIZED_EVENT);
    });

    return {
        listenToSelectableTableResize(element: HTMLElement): void {
            resize_observer.observe(element);
        },
        removeListener(element: HTMLElement): void {
            resize_observer.unobserve(element);
        },
    };
};
