/**
 *  Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import {
    deleteUserPreferenciesForFolderInProject,
    getPreferenceForEmbeddedDisplay,
    patchUserPreferenciesForFolderInProject,
    removeUserPreferenceForEmbeddedDisplay,
    setNarrowModeForEmbeddedDisplay,
} from "../../api/preferencies-rest-querier";
import type { PreferenciesState } from "./preferencies-default-state";
import type { ActionContext, ActionTree } from "vuex";
import type { Item, RootState } from "../../type";

export interface PreferenciesActions extends ActionTree<PreferenciesState, RootState> {
    readonly setUserPreferenciesForFolder: typeof setUserPreferenciesForFolder;
    readonly displayEmbeddedInNarrowMode: typeof displayEmbeddedInNarrowMode;
    readonly displayEmbeddedInLargeMode: typeof displayEmbeddedInLargeMode;
    readonly getEmbeddedFileDisplayPreference: typeof getEmbeddedFileDisplayPreference;
}

export interface UserPreferenciesFolderSetPayload {
    folder_id: number;
    should_be_closed: boolean;
    user_id: number;
    project_id: number;
}

export const setUserPreferenciesForFolder = async (
    context: ActionContext<PreferenciesState, RootState>,
    payload: UserPreferenciesFolderSetPayload,
): Promise<void> => {
    if (payload.user_id === 0) {
        return;
    }

    try {
        if (payload.should_be_closed) {
            await deleteUserPreferenciesForFolderInProject(
                payload.user_id,
                payload.project_id,
                payload.folder_id,
            );
            return;
        }

        await patchUserPreferenciesForFolderInProject(
            payload.user_id,
            payload.project_id,
            payload.folder_id,
        );
    } catch (exception) {
        await context.dispatch("error/handleErrors", exception);
    }
};

export interface DisplayEmbeddedInNarrowModePayload {
    item: Item;
    user_id: number;
    project_id: number;
}

export const displayEmbeddedInNarrowMode = async (
    context: ActionContext<PreferenciesState, RootState>,
    payload: DisplayEmbeddedInNarrowModePayload,
): Promise<void> => {
    try {
        await setNarrowModeForEmbeddedDisplay(payload.user_id, payload.project_id, payload.item.id);
        context.commit("shouldDisplayEmbeddedInLargeMode", false);
    } catch (exception) {
        await context.dispatch("error/handleErrors", exception);
    }
};

export interface DisplayEmbeddedInLargeModePayload {
    item: Item;
    user_id: number;
    project_id: number;
}

export const displayEmbeddedInLargeMode = async (
    context: ActionContext<PreferenciesState, RootState>,
    payload: DisplayEmbeddedInLargeModePayload,
): Promise<void> => {
    try {
        await removeUserPreferenceForEmbeddedDisplay(
            payload.user_id,
            payload.project_id,
            payload.item.id,
        );
        context.commit("shouldDisplayEmbeddedInLargeMode", true);
    } catch (exception) {
        await context.dispatch("error/handleErrors", exception);
    }
};

export interface GetEmbeddedFileDisplayPreferencePayload {
    item: Item;
    user_id: number;
    project_id: number;
}

export const getEmbeddedFileDisplayPreference = async (
    context: ActionContext<PreferenciesState, RootState>,
    payload: GetEmbeddedFileDisplayPreferencePayload,
): Promise<"narrow" | false | null> => {
    try {
        return getPreferenceForEmbeddedDisplay(
            payload.user_id,
            payload.project_id,
            payload.item.id,
        );
    } catch (exception) {
        await context.dispatch("error/handleErrors", exception);
        return null;
    }
};
