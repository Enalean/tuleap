/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import { getItem } from "../../api/rest-querier.js";
import { flagItemAsCreated } from "./flag-item-as-created.js";
import { RETRY_DELAYS, FILE_UPLOAD_UNKNOWN_ERROR } from "../../constants.js";

function updateParentProgress(bytes_total, fake_item, bytes_uploaded, context, parent) {
    if (bytes_total === 0) {
        fake_item.progress = 100;
    } else {
        fake_item.progress = Math.trunc((bytes_uploaded / bytes_total) * 100);
    }
    context.commit("updateFolderProgressbar", parent);
}

export function uploadFile(context, dropped_file, fake_item, docman_item, parent) {
    const uploader = new Upload(dropped_file, {
        uploadUrl: docman_item.file_properties.upload_href,
        retryDelays: RETRY_DELAYS,
        metadata: {
            filename: dropped_file.name,
            filetype: dropped_file.type
        },
        onProgress: (bytes_uploaded, bytes_total) => {
            updateParentProgress(bytes_total, fake_item, bytes_uploaded, context, parent);
        },
        onSuccess: async () => {
            try {
                const file = await getItem(docman_item.id);
                flagItemAsCreated(context, file);
                file.level = fake_item.level;
                context.commit("replaceUploadingFileWithActualFile", [fake_item, file]);
                context.commit("removeFileFromUploadsList", fake_item);
            } catch (exception) {
                fake_item.upload_error = FILE_UPLOAD_UNKNOWN_ERROR;
                fake_item.is_uploading = false;
                context.commit("removeItemFromFolderContent", fake_item);
            } finally {
                context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
            }
        },
        onError: ({ originalRequest }) => {
            fake_item.is_uploading = false;
            fake_item.upload_error = originalRequest.statusText;

            context.commit("removeItemFromFolderContent", fake_item);
        }
    });

    uploader.start();

    return uploader;
}

export function uploadVersion(context, dropped_file, item, new_version) {
    const uploader = new Upload(dropped_file, {
        uploadUrl: new_version.upload_href,
        retryDelays: RETRY_DELAYS,
        metadata: {
            filename: dropped_file.name,
            filetype: dropped_file.type
        },
        onProgress: (bytes_uploaded, bytes_total) => {
            updateParentProgress(bytes_total, item, bytes_uploaded, context, parent);
        },
        onSuccess: () => {
            item.progress = null;
            item.is_uploading_new_version = false;
            const now = Date.now();
            item.file_properties.html_url = item.file_properties.html_url + "&update_time=" + now;
            item.last_update_date = now;
        },
        onError: ({ originalRequest }) => {
            item.upload_error = originalRequest.statusText;
        }
    });

    uploader.start();

    return uploader;
}
