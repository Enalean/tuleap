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

import type { EmitterProvider, UpdateWidgetTitleEvent } from "./helpers/emitter-provider";
import { UPDATE_WIDGET_TITLE_EVENT } from "./helpers/emitter-provider";

export type WidgetTitleUpdater = {
    listenToUpdateTitle(): void;
    removeListener(): void;
};

export const WidgetTitleUpdater = (
    emitter: EmitterProvider,
    title_element: HTMLElement,
): WidgetTitleUpdater => {
    return {
        listenToUpdateTitle(): void {
            emitter.on(UPDATE_WIDGET_TITLE_EVENT, (event: UpdateWidgetTitleEvent): void => {
                title_element.textContent = event.new_title;
            });
        },
        removeListener(): void {
            emitter.off(UPDATE_WIDGET_TITLE_EVENT);
        },
    };
};
