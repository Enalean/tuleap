/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { getFolderContent } from "../../api/rest-querier";
import { handleErrors } from "./handle-errors";
import type { ActionContext } from "vuex";
import type { Folder, RootState } from "../../type";

export async function loadFolderContent(
    context: ActionContext<RootState, RootState>,
    folder_id: number,
    loading_current_folder_promise: Promise<Folder>,
): Promise<void> {
    try {
        context.commit("beginLoading");
        context.commit("saveFolderContent", []);

        const [folder_content] = await Promise.all([
            getFolderContent(folder_id),
            loading_current_folder_promise,
        ]);

        context.commit("saveFolderContent", folder_content);
    } catch (exception) {
        handleErrors(context, exception);
    } finally {
        context.commit("stopLoading");
    }
}
