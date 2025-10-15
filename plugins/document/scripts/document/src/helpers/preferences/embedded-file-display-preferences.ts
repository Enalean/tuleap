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
import type {
    EMBEDDED_FILE_DISPLAY_LARGE,
    EMBEDDED_FILE_DISPLAY_NARROW,
    EmbeddedFileDisplayPreference,
    Item,
    RootState,
} from "../../type";
import type { ActionContext } from "vuex";
import { Option } from "@tuleap/option";
import {
    getPreferenceForEmbeddedDisplay,
    removeUserPreferenceForEmbeddedDisplay,
    setNarrowModeForEmbeddedDisplay,
} from "../../api/preferences-rest-querier";

export function getEmbeddedFileDisplayPreference(
    context: ActionContext<RootState, RootState>,
    item: Item,
    user_id: number,
    project_id: number,
): Promise<Option<EmbeddedFileDisplayPreference>> {
    return getPreferenceForEmbeddedDisplay(user_id, project_id, item.id).match(
        (preference) => Option.fromValue(preference),
        (fault): Option<EmbeddedFileDisplayPreference> => {
            context.dispatch("error/handleErrors", fault);
            return Option.nothing();
        },
    );
}

export function displayEmbeddedInNarrowMode(
    context: ActionContext<RootState, RootState>,
    item: Item,
    user_id: number,
    project_id: number,
): Promise<Option<typeof EMBEDDED_FILE_DISPLAY_NARROW>> {
    return setNarrowModeForEmbeddedDisplay(user_id, project_id, item.id).match(
        (preference) => Option.fromValue(preference),
        (fault): Option<typeof EMBEDDED_FILE_DISPLAY_NARROW> => {
            context.dispatch("error/handleErrors", fault);
            return Option.nothing();
        },
    );
}

export function displayEmbeddedInLargeMode(
    context: ActionContext<RootState, RootState>,
    item: Item,
    user_id: number,
    project_id: number,
): Promise<Option<typeof EMBEDDED_FILE_DISPLAY_LARGE>> {
    return removeUserPreferenceForEmbeddedDisplay(user_id, project_id, item.id).match(
        (preference) => Option.fromValue(preference),
        (fault): Option<typeof EMBEDDED_FILE_DISPLAY_LARGE> => {
            context.dispatch("error/handleErrors", fault);
            return Option.nothing();
        },
    );
}
