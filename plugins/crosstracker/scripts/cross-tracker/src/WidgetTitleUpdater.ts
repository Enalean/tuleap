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

import type {
    CreatedQueryEvent,
    EditedQueryEvent,
    EditQueryEvent,
    Events,
    InitializedWithQueryEvent,
    SwitchQueryEvent,
} from "./helpers/widget-events";
import {
    CREATE_NEW_QUERY_EVENT,
    EDIT_QUERY_EVENT,
    INITIALIZED_WITH_QUERY_EVENT,
    NEW_QUERY_CREATED_EVENT,
    QUERY_EDITED_EVENT,
    SWITCH_QUERY_EVENT,
} from "./helpers/widget-events";
import type { Emitter } from "mitt";

export type WidgetTitleUpdater = {
    listenToUpdateTitle(): void;
    removeListener(): void;
};

export const WidgetTitleUpdater = (
    emitter: Emitter<Events>,
    title_element: HTMLElement,
    default_title: string,
): WidgetTitleUpdater => {
    const updateTitle = (
        event:
            | SwitchQueryEvent
            | InitializedWithQueryEvent
            | CreatedQueryEvent
            | EditedQueryEvent
            | EditQueryEvent,
    ): void => {
        title_element.textContent = event.query.title;
    };

    const resetTitleToDefault = (): void => {
        title_element.textContent = default_title;
    };

    return {
        listenToUpdateTitle(): void {
            emitter.on(SWITCH_QUERY_EVENT, updateTitle);
            emitter.on(INITIALIZED_WITH_QUERY_EVENT, updateTitle);
            emitter.on(NEW_QUERY_CREATED_EVENT, updateTitle);
            emitter.on(QUERY_EDITED_EVENT, updateTitle);
            emitter.on(EDIT_QUERY_EVENT, updateTitle);

            emitter.on(CREATE_NEW_QUERY_EVENT, resetTitleToDefault);
        },
        removeListener(): void {
            emitter.off(SWITCH_QUERY_EVENT, updateTitle);
            emitter.off(INITIALIZED_WITH_QUERY_EVENT, updateTitle);
            emitter.off(NEW_QUERY_CREATED_EVENT, updateTitle);
            emitter.off(QUERY_EDITED_EVENT, updateTitle);
            emitter.off(EDIT_QUERY_EVENT, updateTitle);

            emitter.off(CREATE_NEW_QUERY_EVENT, resetTitleToDefault);
        },
    };
};
