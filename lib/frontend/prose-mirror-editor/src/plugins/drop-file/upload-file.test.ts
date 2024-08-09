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
import type { FileUploadOptions, OnGoingUploadFile } from "./types";
import * as fetch_result from "@tuleap/fetch-result";
import * as download_file from "./helpers/upload-file-helper";
import { okAsync } from "neverthrow";
import { Option } from "@tuleap/option";
import type { OngoingUpload } from "./plugin-drop-file";

describe("uploadFile", () => {
    describe("uploadAndDisplayFileInEditor", () => {
        let file: File, other_file: File;
        let options: FileUploadOptions;
        const upload_url = "upload_url";
        const max_size_upload = 123456789;
        let uploadFileOnServerMock: MockInstance;
        let uploadFileMock: MockInstance;

        function mockFileList(files: File[]): FileList {
            const input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("name", "file-upload");
            input.multiple = true;
            const fileList: FileList = Object.create(input.files);
            for (let i = 0; i < files.length; i++) {
                fileList[i] = files[i];
            }
            Object.defineProperty(fileList, "length", { value: files.length });
            return fileList;
        }

        beforeEach(() => {
            uploadFileOnServerMock = vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({
                    id: 1,
                    upload_href: "upload_href",
                    download_href: "download_href",
                } as unknown),
            );

            uploadFileMock = vi
                .spyOn(download_file, "uploadFile")
                .mockResolvedValue(Option.nothing<OngoingUpload>());
            file = new File(["123"], "file_name.png", { type: "image/png" });
            other_file = new File(["456"], "other.png", { type: "image/png" });
            const upload_files: Map<number, OnGoingUploadFile> = new Map();

            options = {
                upload_url,
                max_size_upload,
                upload_files,
                onErrorCallback: vi.fn(),
                onProgressCallback: vi.fn(),
                onSuccessCallback: vi.fn(),
            };
        });

        describe("when file size exceeds max upload size", () => {
            it("should call error callback with max size upload exceeded error", () => {
                uploadAndDisplayFileInEditor(mockFileList([file, other_file]), {
                    ...options,
                    max_size_upload: 1,
                });
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

                uploadAndDisplayFileInEditor(mockFileList([file_with_invalid_type]), options);
                expect(options.onErrorCallback).toHaveBeenCalledWith(new InvalidFileUploadError());
            });
        });

        it("should upload file on server", async () => {
            await uploadAndDisplayFileInEditor(mockFileList([file, other_file]), options);
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
                await uploadAndDisplayFileInEditor(mockFileList([file, other_file]), options);
                expect(options.onErrorCallback).toHaveBeenCalledWith(new UploadError());
            });
        });

        it("should download file with tus client", async () => {
            await uploadAndDisplayFileInEditor(mockFileList([file, other_file]), options);
            const files = new Map();
            files.set(0, { file_name: file.name, progress: 0 });
            files.set(1, { file_name: other_file.name, progress: 0 });
            expect(uploadFileMock).toHaveBeenCalledWith(
                files,
                0,
                file,
                "upload_href",
                options.onProgressCallback,
            );
            expect(uploadFileMock).toHaveBeenCalledWith(
                files,
                1,
                other_file,
                "upload_href",
                options.onProgressCallback,
            );
        });

        describe("when download file with tus client failed", () => {
            it("should call error callback with an upload error", async () => {
                uploadFileMock.mockRejectedValueOnce(new Error());
                await expect(() =>
                    uploadAndDisplayFileInEditor(mockFileList([file]), options),
                ).rejects.toThrowError();
                expect(options.onErrorCallback).toHaveBeenCalledWith(new UploadError());
            });
        });

        it("should call success callback", async () => {
            await uploadAndDisplayFileInEditor(mockFileList([file]), options);
            expect(options.onSuccessCallback).toHaveBeenCalledWith(1, "download_href");
        });
    });
});
