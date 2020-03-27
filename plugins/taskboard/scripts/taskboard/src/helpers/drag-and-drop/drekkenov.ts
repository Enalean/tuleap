/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { Drekkenov, DrekkenovInitOptions } from "./types";
import { DrekkenovState } from "./DrekkenovState";

export {
    Drekkenov,
    DrekkenovInitOptions,
    DragCallbackParameter,
    DragDropCallbackParameter,
    PossibleDropCallbackParameter,
    SuccessfulDropCallbackParameter,
} from "./types";

/**
 * init()
 *
 * Limits:
 * Draggable elements MUST have a `draggable="true"` attribute.
 * Dropzones cannot also be draggable
 *
 */
export function init(options: DrekkenovInitOptions): Drekkenov {
    const state: DrekkenovState = new DrekkenovState(options, document);
    const dragStart = state.createDragStartHandler();
    document.addEventListener("dragstart", dragStart);

    const drekkenov: Drekkenov = {
        destroy(): void {
            state.cleanup();
            document.removeEventListener("dragstart", dragStart);
        },
    };
    return drekkenov;
}
