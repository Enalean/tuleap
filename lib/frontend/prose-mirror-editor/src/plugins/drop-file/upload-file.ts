/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import type { FileUploadOptions } from "./types";
import { InvalidFileUploadError, MaxSizeUploadExceededError, UploadError } from "./types";
import { uploadFile } from "./helpers/upload-file-helper-helper";
import { postJSON, rawUri, uri } from "@tuleap/fetch-result";

export const VALID_FILE_TYPES = [
    "image/png",
    "image/jpeg",
    "image/jpg",
    "image/webp",
    "image/gif",
    "image/svg+xml",
];

export type PostFileResponse = {
    download_href: string;
    id: number;
    upload_href: string;
};

export function fileUploadHandler(options: FileUploadOptions) {
    return function handler(event: DragEvent): void {
        event.preventDefault();
        // Note: Drop multiple file seems to not work with ProseMirror.
        // It won't trigger an error but the DragEvent always returns only one file.
        const files = event.dataTransfer?.files;
        if (!files || files.length !== 1) {
            options.onErrorCallback(new UploadError());
            return;
        }
        uploadAndDisplayFileInEditor(files[0], options);
    };
}

export function isFileTypeValid(file: File): boolean {
    return VALID_FILE_TYPES.includes(file.type);
}

export async function uploadAndDisplayFileInEditor(
    file: File,
    options: FileUploadOptions,
): Promise<void> {
    const {
        upload_url,
        max_size_upload,
        onStartCallback,
        onErrorCallback,
        onSuccessCallback,
        onProgressCallback,
    } = options;

    onStartCallback();
    if (file.size > max_size_upload) {
        onErrorCallback(new MaxSizeUploadExceededError(max_size_upload));
        return;
    }

    if (!isFileTypeValid(file)) {
        onErrorCallback(new InvalidFileUploadError());
        return;
    }

    await postJSON<PostFileResponse>(uri`${rawUri(upload_url)}`, {
        name: file.name,
        file_size: file.size,
        file_type: file.type,
    }).match(
        async (response) => {
            if (!response.upload_href) {
                onErrorCallback(new UploadError());
                return;
            }

            try {
                await uploadFile(file, response.upload_href, onProgressCallback);
            } catch (error) {
                onErrorCallback(new UploadError());
                throw error;
            }
            onSuccessCallback(response.id, response.download_href);
        },
        () => {
            onErrorCallback(new UploadError());
        },
    );
}
