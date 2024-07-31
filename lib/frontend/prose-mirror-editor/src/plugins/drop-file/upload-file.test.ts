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
import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { uploadAndDisplayFileInEditor } from "./upload-file";
import { InvalidFileUploadError, MaxSizeUploadExceededError, UploadError } from "./types";
import type { FileUploadOptions } from "./types";
import * as fetch_result from "@tuleap/fetch-result";
import * as download_file from "./helpers/upload-file-helper-helper";
import { okAsync } from "neverthrow";

describe("uploadFile", () => {
    describe("uploadAndDisplayFileInEditor", () => {
        let file: File;
        let options: FileUploadOptions;
        const upload_url = "upload_url";
        const max_size_upload = 123456789;
        let uploadFileOnServerMock: MockInstance;
        let uploadFileMock: MockInstance;

        beforeEach(() => {
            uploadFileOnServerMock = vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({
                    id: 1,
                    upload_href: "upload_href",
                    download_href: "download_href",
                } as unknown),
            );

            uploadFileMock = vi.spyOn(download_file, "uploadFile").mockResolvedValue();
            file = new File(["123"], "file_name.png", { type: "image/png" });

            options = {
                upload_url,
                max_size_upload,
                onErrorCallback: vi.fn(),
                onProgressCallback: vi.fn(),
                onStartCallback: vi.fn(),
                onSuccessCallback: vi.fn(),
            };
        });

        it("should call start callback", () => {
            uploadAndDisplayFileInEditor(file, options);
            expect(options.onStartCallback).toHaveBeenCalled();
        });

        describe("when file size exceeds max upload size", () => {
            it("should call error callback with max size upload exceeded error", () => {
                uploadAndDisplayFileInEditor(file, { ...options, max_size_upload: 1 });
                expect(options.onErrorCallback).toHaveBeenCalledWith(
                    new MaxSizeUploadExceededError(max_size_upload),
                );
            });
        });

        describe("when file type is not valid", () => {
            it("should call error callback with an upload error", () => {
                const file_with_invalid_type = new File(["123"], "file_name.pdf", {
                    type: "application/pdf",
                });

                uploadAndDisplayFileInEditor(file_with_invalid_type, options);
                expect(options.onErrorCallback).toHaveBeenCalledWith(new InvalidFileUploadError());
            });
        });

        it("should upload file on server", async () => {
            await uploadAndDisplayFileInEditor(file, options);
            expect(uploadFileOnServerMock).toHaveBeenCalledWith(expect.anything(), {
                file_size: 3,
                file_type: "image/png",
                name: "file_name.png",
            });
        });

        describe("when upload file on server don't return upload_href", () => {
            it("should call success callback", async () => {
                uploadFileOnServerMock = vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                    okAsync({
                        id: 1,
                        download_href: "download_href",
                    } as unknown),
                );
                await uploadAndDisplayFileInEditor(file, options);
                expect(options.onErrorCallback).toHaveBeenCalledWith(new UploadError());
            });
        });

        it("should download file with tus client", async () => {
            await uploadAndDisplayFileInEditor(file, options);
            expect(uploadFileMock).toHaveBeenCalledWith(
                file,
                "upload_href",
                options.onProgressCallback,
            );
        });

        describe("when download file with tus client failed", () => {
            it("should call error callback with an upload error", async () => {
                uploadFileMock.mockRejectedValueOnce(new Error());
                await expect(() =>
                    uploadAndDisplayFileInEditor(file, options),
                ).rejects.toThrowError();
                expect(options.onErrorCallback).toHaveBeenCalledWith(new UploadError());
            });
        });

        it("should call success callback", async () => {
            await uploadAndDisplayFileInEditor(file, options);
            expect(options.onSuccessCallback).toHaveBeenCalledWith(1, "download_href");
        });
    });
});
