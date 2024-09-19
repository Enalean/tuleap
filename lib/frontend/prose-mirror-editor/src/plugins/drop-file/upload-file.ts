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
import type { FileUploadOptions, OnGoingUploadFile } from "./types";
import { InvalidFileUploadError, MaxSizeUploadExceededError, UploadError } from "./types";
import { uploadFile } from "./helpers/upload-file-helper";
import { postJSON, rawUri, uri } from "@tuleap/fetch-result";
import type { OngoingUpload } from "./plugin-drop-file";
import { Option } from "@tuleap/option";
import type { GetText } from "@tuleap/gettext";
import type { Upload } from "tus-js-client";

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

export function fileUploadHandler(
    options: FileUploadOptions,
    gettext_provider: GetText,
    uploaders: Array<Upload>,
) {
    return function handler(event: DragEvent): Promise<Option<ReadonlyArray<OngoingUpload>>> {
        event.preventDefault();
        const files = event.dataTransfer?.files;
        if (!files) {
            options.onErrorCallback(new UploadError(gettext_provider), "");
            return Promise.resolve(Option.nothing<ReadonlyArray<OngoingUpload>>());
        }
        return uploadAndDisplayFileInEditor(files, options, gettext_provider, uploaders);
    };
}

function isFileTypeValid(file: File): boolean {
    return VALID_FILE_TYPES.includes(file.type);
}

export async function uploadAndDisplayFileInEditor(
    files: FileList,
    options: FileUploadOptions,
    gettext_provider: GetText,
    uploaders: Array<Upload>,
): Promise<Option<ReadonlyArray<OngoingUpload>>> {
    const {
        upload_url,
        max_size_upload,
        onStartUploadCallback,
        onErrorCallback,
        onSuccessCallback,
        onProgressCallback,
    } = options;

    const upload_files: OnGoingUploadFile[] = onStartUploadCallback(files);
    const ongoing_uploads: Array<OngoingUpload> = [];
    for (const file of files) {
        if (file.size > max_size_upload) {
            onErrorCallback(
                new MaxSizeUploadExceededError(max_size_upload, gettext_provider),
                file.name,
            );
            return Promise.resolve(Option.fromValue(ongoing_uploads));
        }

        if (!isFileTypeValid(file)) {
            onErrorCallback(new InvalidFileUploadError(gettext_provider), file.name);
            return Promise.resolve(Option.fromValue(ongoing_uploads));
        }

        const optional_ongoing_upload: Option<OngoingUpload> = await postJSON<PostFileResponse>(
            uri`${rawUri(upload_url)}`,
            {
                name: file.name,
                file_size: file.size,
                file_type: file.type,
            },
        ).match(
            async (response): Promise<Option<OngoingUpload>> => {
                if (!response.upload_href) {
                    onErrorCallback(new UploadError(gettext_provider), file.name);
                    return Promise.resolve(Option.nothing<OngoingUpload>());
                }

                try {
                    const ongoing_upload = await uploadFile(
                        upload_files,
                        file,
                        response.upload_href,
                        onProgressCallback,
                        uploaders,
                    );
                    onSuccessCallback(response.id, response.download_href, file.name);

                    return ongoing_upload;
                } catch (error) {
                    onErrorCallback(new UploadError(gettext_provider), file.name);
                    throw error;
                }
            },
            (): Promise<Option<OngoingUpload>> => {
                onErrorCallback(new UploadError(gettext_provider), file.name);
                return Promise.resolve(Option.nothing<OngoingUpload>());
            },
        );
        optional_ongoing_upload.apply((ongoing_upload) => {
            ongoing_uploads.push(ongoing_upload);
        });
    }

    return Promise.resolve(Option.fromValue([...ongoing_uploads]));
}
