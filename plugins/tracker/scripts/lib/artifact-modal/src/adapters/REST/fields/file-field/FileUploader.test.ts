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

import type { ResultAsync } from "neverthrow";
import { isFault } from "@tuleap/fault";
import type { Fault } from "@tuleap/fault";
import * as tus from "tus-js-client";
import type { FileUploaded } from "../../../../domain/fields/file-field/FileUploaded";
import { FileUploader } from "./FileUploader";
import type { FinishFileUploadCommand } from "../../../../domain/fields/file-field/FinishFileUploadCommand";

const FILE_ID = 706;
const UPLOAD_HREF = `/uploads/tracker/file/${FILE_ID}`;

describe(`FileUploader`, () => {
    let file_handle: File;
    beforeEach(() => {
        file_handle = { size: 8646 } as File;
    });

    const uploadFile = (): ResultAsync<FileUploaded, Fault> => {
        const file: FinishFileUploadCommand = {
            file_id: FILE_ID,
            file_handle,
            upload_href: UPLOAD_HREF,
        };

        const uploader = FileUploader();
        return uploader.uploadFile(file);
    };

    it(`uploads a file with the Tus protocol and returns the file ID`, async () => {
        let tus_file_handle;
        let tus_options: { uploadUrl?: string | null } = { uploadUrl: null };
        jest.spyOn(tus, "Upload").mockImplementation((file, options) => {
            tus_file_handle = file;
            tus_options = options;

            return {
                start: (): void => options.onSuccess?.(),
            } as tus.Upload;
        });

        const result = await uploadFile();

        if (!result.isOk()) {
            throw Error("Expected an Ok");
        }
        expect(result.value.file_id).toBe(FILE_ID);
        expect(tus_file_handle).toBe(file_handle);
        expect(tus_options.uploadUrl).toBe(UPLOAD_HREF);
    });

    it(`when there is an error, it will wrap it in a Fault`, async () => {
        jest.spyOn(tus, "Upload").mockImplementation(
            (file, options) =>
                ({
                    start: (): void => options.onError?.(Error("Error during TUS upload")),
                }) as tus.Upload,
        );

        const result = await uploadFile();

        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        expect(isFault(result.error)).toBe(true);
    });
});
