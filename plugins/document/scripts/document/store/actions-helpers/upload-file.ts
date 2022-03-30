/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { Upload } from "tus-js-client";
import { getItem } from "../../api/rest-querier";
import { flagItemAsCreated } from "./flag-item-as-created";
import { FILE_UPLOAD_UNKNOWN_ERROR } from "../../constants";
import type { CreatedItem, CreatedItemFileProperties, FakeItem, Folder, State } from "../../type";
import type { ActionContext } from "vuex";
import { isFile } from "../../helpers/type-check-helper";
import { getParentFolder } from "./item-retriever";

function updateParentProgress(
    bytes_total: number,
    fake_item: FakeItem,
    bytes_uploaded: number,
    context: ActionContext<State, State>,
    parent: Folder
): void {
    if (bytes_total === 0) {
        fake_item.progress = 100;
    } else {
        fake_item.progress = Math.trunc((bytes_uploaded / bytes_total) * 100);
    }
    context.commit("updateFolderProgressbar", parent);
}

export function uploadFile(
    context: ActionContext<State, State>,
    dropped_file: File,
    fake_item: FakeItem,
    docman_item: CreatedItem,
    parent: Folder
): Upload {
    const uploader = new Upload(dropped_file, {
        uploadUrl: docman_item.file_properties?.upload_href ?? null,
        metadata: {
            filename: dropped_file.name,
            filetype: dropped_file.type,
        },
        onProgress: (bytes_uploaded, bytes_total): void => {
            updateParentProgress(bytes_total, fake_item, bytes_uploaded, context, parent);
        },
        onSuccess: async (): Promise<void> => {
            try {
                const file = await getItem(docman_item.id);
                if (isFile(file)) {
                    flagItemAsCreated(context, file);
                    if (fake_item.level !== undefined) {
                        file.level = fake_item.level;
                    }
                    context.commit("replaceUploadingFileWithActualFile", [fake_item, file]);
                    context.commit("removeFileFromUploadsList", fake_item);
                }
            } catch (exception) {
                fake_item.upload_error = FILE_UPLOAD_UNKNOWN_ERROR;
                fake_item.is_uploading = false;
                context.commit("removeItemFromFolderContent", fake_item);
            } finally {
                context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
            }
        },
        onError: (error: Error): void => {
            fake_item.is_uploading = false;
            fake_item.upload_error = error.message;

            context.commit("removeItemFromFolderContent", fake_item);
        },
    });

    uploader.start();

    return uploader;
}

export function uploadVersion(
    context: ActionContext<State, State>,
    dropped_file: File,
    updated_file: FakeItem,
    new_version: CreatedItemFileProperties
): Upload {
    const parent_folder = getParentFolder(
        context.state.folder_content,
        updated_file,
        context.state.current_folder
    );

    const uploader = new Upload(dropped_file, {
        uploadUrl: new_version.upload_href,
        metadata: {
            filename: dropped_file.name,
            filetype: dropped_file.type,
        },
        onProgress: (bytes_uploaded, bytes_total): void => {
            updateParentProgress(bytes_total, updated_file, bytes_uploaded, context, parent_folder);
        },
        onSuccess: async (): Promise<void> => {
            updated_file.progress = null;
            updated_file.is_uploading_new_version = false;
            updated_file.last_update_date = new Date();
            context.commit("removeFileFromUploadsList", updated_file);

            const new_item_version = await getItem(updated_file.id);
            context.commit("replaceFileWithNewVersion", [updated_file, new_item_version]);
            context.commit("replaceUploadingFileWithActualFile", [updated_file, new_item_version]);
        },
        onError: (error: Error): void => {
            updated_file.upload_error = error.message;
        },
    });
    uploader.start();
    return uploader;
}

export function uploadVersionFromEmpty(
    context: ActionContext<State, State>,
    dropped_file: File,
    updated_empty: FakeItem,
    new_version: CreatedItemFileProperties
): Upload {
    const parent_folder = getParentFolder(
        context.state.folder_content,
        updated_empty,
        context.state.current_folder
    );

    const uploader = new Upload(dropped_file, {
        uploadUrl: new_version.upload_href,
        metadata: {
            filename: dropped_file.name,
            filetype: dropped_file.type,
        },
        onProgress: (bytes_uploaded, bytes_total): void => {
            updateParentProgress(
                bytes_total,
                updated_empty,
                bytes_uploaded,
                context,
                parent_folder
            );
        },
        onSuccess: async (): Promise<void> => {
            updated_empty.progress = null;
            updated_empty.is_uploading_new_version = false;
            updated_empty.last_update_date = new Date();
            context.commit("removeFileFromUploadsList", updated_empty);
            const new_item_version = await getItem(updated_empty.id);
            context.commit("removeItemFromFolderContent", new_item_version);
            context.commit("addJustCreatedItemToFolderContent", new_item_version);
            context.commit("updateCurrentItemForQuickLokDisplay", new_item_version);
            context.commit("replaceUploadingFileWithActualFile", [updated_empty, new_item_version]);
        },
        onError: (error: Error): void => {
            updated_empty.upload_error = error.message;
        },
    });

    uploader.start();

    return uploader;
}
