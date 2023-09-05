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

import { ResultAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { Upload } from "tus-js-client";
import type { FinishFileUpload } from "../../../../domain/fields/file-field/FinishFileUpload";
import type { FileUploaded } from "../../../../domain/fields/file-field/FileUploaded";

export const FileUploader = (): FinishFileUpload => ({
    uploadFile(file): ResultAsync<FileUploaded, Fault> {
        return ResultAsync.fromPromise(
            new Promise((resolve, reject) => {
                const uploader = new Upload(file.file_handle, {
                    uploadUrl: file.upload_href,
                    onSuccess: () => resolve({ file_id: file.file_id }),
                    onError: (error): void => reject(error),
                });
                uploader.start();
            }),
            (error) => {
                return error instanceof Error
                    ? Fault.fromError(error)
                    : Fault.fromMessage("Error during file upload");
            },
        );
    },
});
