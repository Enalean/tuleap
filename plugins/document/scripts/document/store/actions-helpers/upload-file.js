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

export function uploadFile(context, dropped_file, fake_item, docman_item) {
    const uploader = new Upload(dropped_file, {
        uploadUrl: docman_item.file_properties.upload_href,
        retryDelays: [0, 1000, 3000, 5000],
        metadata: {
            filename: dropped_file.name,
            filetype: dropped_file.type
        },
        onProgress: (bytes_uploaded, bytes_total) => {
            fake_item.progress = Math.trunc((bytes_uploaded / bytes_total) * 100);
        },
        onSuccess: async () => {
            const file = await getItem(docman_item.id);
            flagItemAsCreated(context, file);
            context.commit("replaceUploadingFileWithActualFile", [fake_item, file]);
            context.commit("removeFileFromUploadsList", fake_item);
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
