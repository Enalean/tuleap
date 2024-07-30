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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { useUploadFile } from "@/composables/useUploadFile";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import { UploadError } from "@tuleap/prose-mirror-editor";

describe("useUploadFile", () => {
    beforeEach(() => {
        mockStrictInject([[UPLOAD_MAX_SIZE, 222]]);
    });

    describe("file_upload_options", () => {
        it("should be initialized properly", () => {
            const { file_upload_options } = useUploadFile("upload_url", vi.fn());

            expect(file_upload_options.upload_url).toBe("upload_url");
            expect(file_upload_options.max_size_upload).toBe(222);
            expect(file_upload_options.onErrorCallback).toBeDefined();
            expect(file_upload_options.onSuccessCallback).toBeDefined();
            expect(file_upload_options.onProgressCallback).toBeDefined();
        });
    });

    describe("error_message", () => {
        it("should return the upload error message", () => {
            const { file_upload_options, error_message } = useUploadFile("upload_url", vi.fn());

            file_upload_options.onErrorCallback(new UploadError());

            expect(error_message.value).toBe("An error occurred during upload");
        });
    });

    describe("upload_progress", () => {
        describe("while the upload is in progress", () => {
            it("update progress", () => {
                const { file_upload_options, progress } = useUploadFile("upload_url", vi.fn());

                file_upload_options.onProgressCallback(88);

                expect(progress.value).toBe(88);
            });
        });
        describe("resetProgressCallback", () => {
            it("should reset progress", () => {
                const { resetProgressCallback, progress, file_upload_options } = useUploadFile(
                    "upload_url",
                    vi.fn(),
                );

                file_upload_options.onProgressCallback(88);
                expect(progress.value).toBe(88);

                resetProgressCallback();
                expect(progress.value).toBe(0);
            });
        });
    });
});
