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
import { createNewVersion } from "../../api/rest-querier";
import type { ActionContext } from "vuex";
import type { ApprovalTable, ItemFile, RootState, CreatedItemFileProperties } from "../../type";
import { uploadVersion } from "./upload-file";

export async function uploadNewVersion(
    context: ActionContext<RootState, RootState>,
    [item, uploaded_file, version_title, changelog, is_file_locked, approval_table_action]: [
        ItemFile,
        File,
        string,
        string,
        boolean,
        ApprovalTable | null,
    ],
): Promise<void> {
    const new_version = await createNewVersion(
        item,
        version_title,
        changelog,
        uploaded_file,
        is_file_locked,
        approval_table_action,
    );

    if (uploaded_file.size === 0) {
        return;
    }

    context.commit("addFileInUploadsList", item);
    item.progress = null;
    item.upload_error = null;
    item.is_uploading_new_version = false;

    uploadVersionAndAssignUploader(context, item, uploaded_file, new_version);
}

function uploadVersionAndAssignUploader(
    context: ActionContext<RootState, RootState>,
    item: ItemFile,
    uploaded_file: File,
    new_version: CreatedItemFileProperties,
): void {
    item.uploader = uploadVersion(context, uploaded_file, item, new_version);
}
