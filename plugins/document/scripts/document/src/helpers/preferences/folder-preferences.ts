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
import type { RootState } from "../../type";
import {
    deleteUserPreferencesForFolderInProject,
    patchUserPreferencesForFolderInProject,
} from "../../api/preferences-rest-querier";
import type { ActionContext } from "vuex";

export async function setUserPreferencesForFolder(
    context: ActionContext<RootState, RootState>,
    folder_id: number,
    should_be_closed: boolean,
    user_id: number,
    project_id: number,
): Promise<void> {
    if (user_id === 0) {
        return;
    }

    if (should_be_closed) {
        await deleteUserPreferencesForFolderInProject(user_id, project_id, folder_id).mapErr(
            (fault) => context.dispatch("error/handleErrors", fault),
        );
        return;
    }

    await patchUserPreferencesForFolderInProject(user_id, project_id, folder_id).mapErr((fault) =>
        context.dispatch("error/handleErrors", fault),
    );
}
