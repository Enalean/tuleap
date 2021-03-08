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

import { handleErrors } from "../actions-helpers/handle-errors";
import {
    addUserLegacyUIPreferency,
    deleteUserPreferenciesForFolderInProject,
    getPreferenceForEmbeddedDisplay,
    patchUserPreferenciesForFolderInProject,
    removeUserPreferenceForEmbeddedDisplay,
    setNarrowModeForEmbeddedDisplay,
} from "../../api/preferencies-rest-querier";

export const setUserPreferenciesForFolder = (context, [folder_id, should_be_closed]) => {
    if (context.rootState.configuration.user_id === 0) {
        return;
    }

    try {
        if (should_be_closed) {
            return deleteUserPreferenciesForFolderInProject(
                context.rootState.configuration.user_id,
                context.rootState.configuration.project_id,
                folder_id
            );
        }

        return patchUserPreferenciesForFolderInProject(
            context.rootState.configuration.user_id,
            context.rootState.configuration.project_id,
            folder_id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const setUserPreferenciesForUI = async (context) => {
    try {
        return await addUserLegacyUIPreferency(
            context.rootState.configuration.user_id,
            context.rootState.configuration.project_id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const displayEmbeddedInNarrowMode = async (context, item) => {
    try {
        await setNarrowModeForEmbeddedDisplay(
            context.rootState.configuration.user_id,
            context.rootState.configuration.project_id,
            item.id
        );
        context.commit("shouldDisplayEmbeddedInLargeMode", false);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const displayEmbeddedInLargeMode = async (context, item) => {
    try {
        await removeUserPreferenceForEmbeddedDisplay(
            context.rootState.configuration.user_id,
            context.rootState.configuration.project_id,
            item.id
        );
        context.commit("shouldDisplayEmbeddedInLargeMode", true);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const getEmbeddedFileDisplayPreference = async (context, item) => {
    try {
        return await getPreferenceForEmbeddedDisplay(
            context.rootState.configuration.user_id,
            context.rootState.configuration.project_id,
            item.id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    }
};
