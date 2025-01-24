/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { uploadFile } from "./helpers/upload-file-helper";
import { postJSON, rawUri, uri } from "@tuleap/fetch-result";
import { Option } from "@tuleap/option";
import type { FileUploadOptions, OngoingUpload, OnGoingUploadFile } from "./file-upload-options";
import { GenericUploadError } from "./upload-file.error";
import type { Upload } from "tus-js-client";
import { getGettextProvider } from "./helpers/get-gettext-provider";

export * from "./file-upload-options";
export type { UploadError } from "./upload-file.error";

type PostFileResponse = {
    download_href: string;
    id: number;
    upload_href: string;
};

export interface FileUploader {
    readonly createOngoingUpload: (
        file: File,
        upload_files: OnGoingUploadFile[],
        options: FileUploadOptions,
    ) => Promise<Option<OngoingUpload>>;
    readonly cancelOngoingUpload: () => Promise<void>;
}

export function getFileUploader(): FileUploader {
    let uploaders: Array<Upload> = [];

    return {
        async createOngoingUpload(
            file: File,
            upload_files: OnGoingUploadFile[],
            options: FileUploadOptions,
        ): Promise<Option<OngoingUpload>> {
            const gettext_provider = await getGettextProvider();

            return postJSON<PostFileResponse>(
                uri`${rawUri(options.post_information.upload_url)}`,
                options.post_information.getUploadJsonPayload(file),
            ).match(
                async (response): Promise<Option<OngoingUpload>> => {
                    if (!response.upload_href) {
                        options.onErrorCallback(
                            new GenericUploadError(gettext_provider),
                            file.name,
                        );
                        return Promise.resolve(Option.nothing<OngoingUpload>());
                    }

                    try {
                        const ongoing_upload = await uploadFile(
                            upload_files,
                            file,
                            response.upload_href,
                            options.onProgressCallback,
                            uploaders,
                        );
                        options.onSuccessCallback(response.id, response.download_href, file.name);

                        return ongoing_upload;
                    } catch (error) {
                        options.onErrorCallback(
                            new GenericUploadError(gettext_provider),
                            file.name,
                        );
                        throw error;
                    }
                },
                (): Promise<Option<OngoingUpload>> => {
                    options.onErrorCallback(new GenericUploadError(gettext_provider), file.name);
                    return Promise.resolve(Option.nothing<OngoingUpload>());
                },
            );
        },
        async cancelOngoingUpload(): Promise<void> {
            for (let i = 0; i < uploaders.length; i++) {
                await uploaders[i].abort(true);
            }

            uploaders = [];
        },
    };
}
