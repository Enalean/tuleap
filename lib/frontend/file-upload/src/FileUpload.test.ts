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

import type { MockInstance } from "vitest";
import { describe, expect, it, beforeEach, vi } from "vitest";
import * as fetch_result from "@tuleap/fetch-result";
import * as download_file from "./helpers/upload-file-helper";
import { okAsync } from "neverthrow";
import { Option } from "@tuleap/option";
import type { FileUploadOptions, OngoingUpload } from "./file-upload-options";
import type { GetText } from "@tuleap/gettext";
import { GenericUploadError } from "./upload-file.error";
import { getFileUploader } from "./FileUpload";

const gettext_provider = {
    gettext: vi.fn(),
} as unknown as GetText;

vi.mock("./helpers/get-gettext-provider", () => {
    return {
        getGettextProvider: (): Promise<GetText> => {
            return Promise.resolve(gettext_provider);
        },
    };
});

describe("FileUpload", () => {
    let file: File, other_file: File;
    let options: FileUploadOptions;
    const upload_url = "upload_url";
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

        uploadFileMock = vi
            .spyOn(download_file, "uploadFile")
            .mockResolvedValue(Option.nothing<OngoingUpload>());
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
            onErrorCallback: vi.fn(),
            onProgressCallback: vi.fn(),
            onSuccessCallback: vi.fn(),
        };
    });

    it("should upload file on server", async () => {
        await getFileUploader().createOngoingUpload(file, [], options);
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

            await getFileUploader().createOngoingUpload(file, [], options);
            expect(options.onErrorCallback).toHaveBeenCalledWith(
                new GenericUploadError(gettext_provider),
                file.name,
            );
        });
    });

    it("should download file with tus client", async () => {
        await getFileUploader().createOngoingUpload(file, [], options);
        expect(uploadFileMock).toHaveBeenCalledWith(
            [],
            file,
            "upload_href",
            options.onProgressCallback,
            [],
        );
        expect(uploadFileMock).toHaveBeenCalledWith(
            [],
            other_file,
            "upload_href",
            options.onProgressCallback,
            [],
        );
    });

    describe("when download file with tus client failed", () => {
        it("should call error callback with an upload error", async () => {
            uploadFileMock.mockRejectedValueOnce(new Error());
            await expect(() =>
                getFileUploader().createOngoingUpload(file, [], options),
            ).rejects.toThrowError();
            expect(options.onErrorCallback).toHaveBeenCalledWith(
                new GenericUploadError(gettext_provider),
                file.name,
            );
        });
    });

    it("should call success callback", async () => {
        await getFileUploader().createOngoingUpload(file, [], options);
        expect(options.onSuccessCallback).toHaveBeenCalledWith(1, "download_href", file.name);
    });
});
