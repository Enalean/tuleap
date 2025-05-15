/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

import { cancelUpload } from "../api/rest-querier";
import type { FakeItem, Folder, Item, ItemFile, RootState } from "../type";
import type { ActionContext } from "vuex";
import { isFakeItem, isFile } from "../helpers/type-check-helper";

export const cancelFileUpload = async (
    context: ActionContext<RootState, RootState>,
    item: ItemFile | FakeItem,
): Promise<void> => {
    try {
        if (item.uploader) {
            item.uploader.abort();
        }
        await cancelUpload(item);
    } catch (e) {
        // do nothing
    } finally {
        context.commit("removeItemFromFolderContent", item);
        context.commit("removeFileFromUploadsList", item);
    }
};

export const cancelVersionUpload = async (
    context: ActionContext<RootState, RootState>,
    item: ItemFile | FakeItem,
): Promise<void> => {
    try {
        if (item.uploader) {
            item.uploader.abort();
        }
        await cancelUpload(item);
    } catch (e) {
        // do nothing
    } finally {
        context.commit("removeVersionUploadProgress", item);
    }
};

export const cancelFolderUpload = (
    context: ActionContext<RootState, RootState>,
    folder: Folder,
): void => {
    try {
        const children = context.state.files_uploads_list.filter(
            (item) => item.parent_id === folder.id,
        );

        children.forEach((child) => {
            if (child.is_uploading_new_version) {
                cancelVersionUpload(context, child);
            } else {
                cancelFileUpload(context, child);
            }
        });
    } catch (e) {
        // do nothing
    } finally {
        context.commit("resetFolderIsUploading", folder);
    }
};

export const cancelAllFileUploads = (
    context: ActionContext<RootState, RootState>,
): Promise<void[]> => {
    return Promise.all(
        context.state.folder_content.reduce(
            (promises: Array<Promise<void>>, item: Item | FakeItem) => {
                if (!(isFile(item) || isFakeItem(item))) {
                    return promises;
                }

                if (!item.is_uploading) {
                    return promises;
                }

                promises.push(cancelFileUpload(context, item));
                return promises;
            },
            [],
        ),
    );
};
