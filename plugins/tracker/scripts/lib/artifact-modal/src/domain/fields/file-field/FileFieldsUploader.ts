/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { ResultAsync, okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { FileFieldValueModel } from "./FileFieldValueModel";
import type { NewFileToAttach } from "./NewFileToAttach";
import type { CreateFileUpload } from "./CreateFileUpload";
import type { FinishFileUpload } from "./FinishFileUpload";
import type { FileUploaded } from "./FileUploaded";
import { FileUploadFault } from "./FileUploadFault";

type FileFieldsUploaderType = {
    uploadAllFileFields(field_values: ReadonlyArray<FileFieldValueModel>): ResultAsync<void, Fault>;
};

const DEFAULT_MIME_TYPE = "application/octet-stream";

export const FileFieldsUploader = (
    upload_creator: CreateFileUpload,
    upload_finisher: FinishFileUpload
): FileFieldsUploaderType => {
    const uploadOneFile = (
        file_field_value: FileFieldValueModel,
        new_file: NewFileToAttach
    ): ResultAsync<void, Fault> => {
        const file_handle = new_file.file;
        if (file_handle === undefined) {
            return okAsync(undefined);
        }

        const file_type = file_handle.type !== "" ? file_handle.type : DEFAULT_MIME_TYPE;

        return upload_creator
            .createFileUpload({
                file_field_id: file_field_value.field_id,
                file_type,
                description: new_file.description,
                file_handle,
            })
            .andThen((file_upload): ResultAsync<FileUploaded, Fault> => {
                if (!file_upload.upload_href) {
                    return okAsync({ file_id: file_upload.file_id });
                }
                return upload_finisher.uploadFile({
                    file_id: file_upload.file_id,
                    upload_href: file_upload.upload_href,
                    file_handle,
                });
            })
            .map((uploaded_file) => {
                file_field_value.value = file_field_value.value.concat([uploaded_file.file_id]);
            })
            .mapErr((fault) => FileUploadFault(fault, file_handle.name));
    };

    return {
        uploadAllFileFields: (field_values): ResultAsync<void, Fault> =>
            ResultAsync.combine(
                field_values.map((file_field_value) =>
                    ResultAsync.combine(
                        file_field_value.temporary_files.map((new_file) =>
                            uploadOneFile(file_field_value, new_file)
                        )
                    )
                )
            ).map(() => {
                // map to void
            }),
    };
};
