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

import type { MockedFunction } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { PluginUploadOptions } from "./upload-file";
import { uploadAndDisplayFileInEditor } from "./upload-file";
import { InvalidFileUploadError, MaxSizeUploadExceededError, NoUploadError } from "./types";
import type { GetText } from "@tuleap/gettext";
import { Option } from "@tuleap/option";
import type { FileUploader, OnGoingUploadFile } from "@tuleap/file-upload";

const gettext_provider = {
    gettext: vi.fn(),
} as unknown as GetText;

describe("uploadFile", () => {
    describe("uploadAndDisplayFileInEditor", () => {
        let file: File, other_file: File;
        let options: PluginUploadOptions;
        let uploader: FileUploader;
        let createOngoingUpload: MockedFunction<FileUploader["createOngoingUpload"]>;
        const upload_url = "upload_url";
        const max_size_upload = 123456789;

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
            createOngoingUpload = vi.fn();
            createOngoingUpload.mockImplementation(() => Promise.resolve(Option.nothing()));
            uploader = {
                createOngoingUpload,
                cancelOngoingUpload: vi.fn(),
            };
            file = new File(["123"], "file_name.png", { type: "image/png" });
            other_file = new File(["456"], "other.png", { type: "image/png" });
            options = {
                post_information: {
                    getUploadJsonPayload(file: File): unknown {
                        return {
                            name: file.name,
                            file_size: file.size,
                            file_type: file.type,
                        };
                    },
                    upload_url,
                },
                max_size_upload,
                onErrorCallback: vi.fn(),
                onStartUploadCallback: vi.fn().mockReturnValue([
                    {
                        file_name: "file_1",
                        progress: 5,
                    },
                    {
                        file_name: "file_2",
                        progress: 0,
                    },
                ]),
                onProgressCallback: vi.fn(),
                onSuccessCallback: vi.fn(),
            };
        });

        describe("When there is no upload_url", () => {
            it("should call error callback with an upload error", async () => {
                await expect(() =>
                    uploadAndDisplayFileInEditor(
                        mockFileList([file]),
                        {
                            ...options,
                            post_information: {
                                ...options.post_information,
                                upload_url: "",
                            },
                        },
                        gettext_provider,
                        uploader,
                    ),
                ).rejects.toThrowError();
                expect(options.onErrorCallback).toHaveBeenCalledWith(
                    new NoUploadError(gettext_provider),
                    "",
                );
                expect(uploader.createOngoingUpload).not.toHaveBeenCalled();
            });
        });

        describe("when file size exceeds max upload size", () => {
            it("should call error callback with max size upload exceeded error", () => {
                uploadAndDisplayFileInEditor(
                    mockFileList([file, other_file]),
                    {
                        ...options,
                        max_size_upload: 1,
                    },
                    gettext_provider,
                    uploader,
                );
                expect(options.onErrorCallback).toHaveBeenCalledWith(
                    new MaxSizeUploadExceededError(max_size_upload, gettext_provider),
                    file.name,
                );
                expect(uploader.createOngoingUpload).not.toHaveBeenCalled();
            });
        });

        describe("when file type is not valid", () => {
            it("should call error callback with an upload error", () => {
                const file_with_invalid_type = new File(["123"], "file_name.pdf", {
                    type: "application/pdf",
                });

                uploadAndDisplayFileInEditor(
                    mockFileList([file_with_invalid_type]),
                    options,
                    gettext_provider,
                    uploader,
                );
                expect(options.onErrorCallback).toHaveBeenCalledWith(
                    new InvalidFileUploadError(gettext_provider),
                    file_with_invalid_type.name,
                );
                expect(uploader.createOngoingUpload).not.toHaveBeenCalled();
            });
        });

        it("should upload file on server", async () => {
            await uploadAndDisplayFileInEditor(
                mockFileList([file, other_file]),
                options,
                gettext_provider,
                uploader,
            );
            const files: OnGoingUploadFile[] = [
                {
                    file_name: "file_1",
                    progress: 5,
                },
                {
                    file_name: "file_2",
                    progress: 0,
                },
            ];
            expect(uploader.createOngoingUpload).toHaveBeenCalledWith(file, files, options);
            expect(uploader.createOngoingUpload).toHaveBeenCalledWith(other_file, files, options);
        });
    });
});
