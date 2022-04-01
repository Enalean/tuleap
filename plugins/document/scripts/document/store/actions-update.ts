/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import Vue from "vue";
import { getErrorMessage } from "./actions-helpers/handle-errors";
import type { ActionContext } from "vuex";
import type { ItemFile, RootState } from "../type";
import { uploadNewVersion } from "./actions-helpers/upload-new-version";
import { FetchWrapperError } from "tlp";

export async function createNewFileVersion(
    context: ActionContext<RootState, RootState>,
    [item, dropped_file]: [ItemFile, File]
): Promise<void> {
    try {
        await uploadNewVersion(context, [item, dropped_file, item.title, "", false, null]);
        Vue.set(item, "updated", true);
    } catch (exception) {
        context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
        if (exception instanceof FetchWrapperError) {
            const error_json = await exception.response.json();
            throw getErrorMessage(error_json);
        }
        throw exception;
    }
}
