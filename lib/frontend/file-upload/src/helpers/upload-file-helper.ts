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

import { type DetailedError, Upload } from "tus-js-client";
import { computedProgress } from "./progress-computation-helper";
import { Option } from "@tuleap/option";
import type { FileUploadOptions, OngoingUpload, OnGoingUploadFile } from "../file-upload-options";

export function uploadFile(
    files: OnGoingUploadFile[],
    file: File,
    upload_href: string,
    onProgressCallback: FileUploadOptions["onProgressCallback"],
    uploaders: Array<Upload>,
): Promise<Option<OngoingUpload>> {
    let uploader: Upload | null;

    const removeUploaderFromList = (): void => {
        if (!uploader) {
            return;
        }

        const index = uploaders.findIndex((file_uploader) => file_uploader === uploader);
        if (index !== -1) {
            uploaders.splice(index, 1);
        }
    };

    return new Promise((resolve, reject): void => {
        uploader = new Upload(file, {
            uploadUrl: upload_href,
            metadata: {
                filename: file.name,
                filetype: file.type,
            },
            onProgress: (bytes_sent: number, bytes_total: number): void => {
                const progress = computedProgress(files, file.name, bytes_sent, bytes_total);
                onProgressCallback(file.name, progress);
            },
            onSuccess: (): void => {
                removeUploaderFromList();
                return resolve(Option.nothing<OngoingUpload>());
            },
            onError: (error: Error | DetailedError): void => {
                return reject(error);
            },
        });
        uploader.start();
        uploaders.push(uploader);
    }).then(() => {
        if (!uploader) {
            throw new Error("The uploader has not been initialized properly.");
        }
        return Option.fromValue({
            cancel: () => {
                uploader?.abort();
                removeUploaderFromList();
            },
        });
    });
}
