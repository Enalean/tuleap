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
import type { UploadSectionFile } from "@/sections/attachments/SectionFileUploader";
import { getSectionFileUploader } from "@/sections/attachments/SectionFileUploader";
import { mockFileList } from "@/helpers/mock-file-list";
import type { UploadError } from "@tuleap/file-upload";
import type {
    FileUploadsCollection,
    OnGoingUploadFileWithId,
} from "@/sections/attachments/FileUploadsCollection";
import type { ManageSectionAttachmentFiles } from "@/sections/attachments/SectionAttachmentFilesManager";
import { FileUploadsCollectionStub } from "@/helpers/stubs/FileUploadsCollectionStub";
import { SectionAttachmentFilesManagerStub } from "@/sections/stubs/SectionAttachmentFilesManagerStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import type { NotificationsCollection } from "@/sections/notifications/NotificationsCollection";
import { NotificationsCollectionStub } from "@/sections/stubs/NotificationsCollectionStub";

function getCurrentSectionUploads(
    section_id: string,
    store_data: OnGoingUploadFileWithId[],
): OnGoingUploadFileWithId[] {
    return store_data.filter((upload) => upload.section_id === section_id);
}

class DummyUploadError extends Error implements UploadError {
    constructor() {
        super();
        this.name = "DummyUploadError";
        this.message = "An error occurred during upload";
    }
}

const upload_max_size = 222;

describe("SectionFileUploader", () => {
    let file_uploads_collection: FileUploadsCollection,
        notifications_collection: NotificationsCollection,
        manage_section_attachments: ManageSectionAttachmentFiles;

    const section_id: string =
        FileUploadsCollectionStub.withUploadsInProgress().pending_uploads.value[0].section_id;

    beforeEach(() => {
        file_uploads_collection = FileUploadsCollectionStub.withUploadsInProgress();
        notifications_collection = NotificationsCollectionStub.withMessages();
        manage_section_attachments = SectionAttachmentFilesManagerStub.forSection(
            FreetextSectionFactory.create(),
        );
    });

    const buildSectionFileUploader = (): UploadSectionFile =>
        getSectionFileUploader(
            section_id,
            manage_section_attachments,
            file_uploads_collection,
            notifications_collection,
            upload_max_size,
        );

    describe("error_message", () => {
        it("should return the upload error message", () => {
            const addNotification = vi.spyOn(notifications_collection, "addNotification");

            const { file_upload_options } = buildSectionFileUploader();

            file_upload_options.onErrorCallback(new DummyUploadError(), "file_name");

            expect(addNotification).toHaveBeenCalledWith({
                message: "An error occurred during upload",
                type: "danger",
            });
        });

        it("should delete the current file pending upload", () => {
            const deleteUpload = vi.spyOn(file_uploads_collection, "deleteUpload");

            const { file_upload_options } = buildSectionFileUploader();

            const current_file = file_uploads_collection.pending_uploads.value[0];
            file_upload_options.onErrorCallback(new DummyUploadError(), current_file.file_name);

            expect(deleteUpload).toHaveBeenCalledWith(current_file.file_id);
        });
    });

    describe("resetProgressCallback", () => {
        it("should reset progress", () => {
            const cancelUploads = vi.spyOn(file_uploads_collection, "cancelSectionUploads");

            const { resetProgressCallback } = buildSectionFileUploader();

            resetProgressCallback();

            expect(cancelUploads).toHaveBeenCalledOnce();
        });
    });

    describe("onStartUploadCallback", () => {
        it("should add the current upload to the store pending uploads", () => {
            const addPendingUpload = vi.spyOn(file_uploads_collection, "addPendingUpload");

            const { file_upload_options } = buildSectionFileUploader();

            const list: FileList = mockFileList([new File(["123"], "file_1")]);
            file_upload_options.onStartUploadCallback(list);

            expect(addPendingUpload).toHaveBeenNthCalledWith(1, "file_1", section_id);
        });
    });

    describe("onSuccessCallback", () => {
        it("should add the current upload to the store pending uploads", () => {
            const add_attachment_to_waiting_list_mock = vi.spyOn(
                manage_section_attachments,
                "addAttachmentToWaitingList",
            );
            const { file_upload_options } = buildSectionFileUploader();

            file_upload_options.onSuccessCallback(123, "download_href", "file_name");
            expect(add_attachment_to_waiting_list_mock).toHaveBeenCalledWith({
                id: 123,
                upload_url: "download_href",
            });
        });

        it("should actualize progress of upload to 100%", () => {
            const { file_upload_options } = buildSectionFileUploader();

            const current_uploads_section = getCurrentSectionUploads(
                section_id,
                file_uploads_collection.pending_uploads.value,
            );
            expect(current_uploads_section[0].progress).toBe(45);

            file_upload_options.onSuccessCallback(
                123,
                "download_href",
                file_uploads_collection.pending_uploads.value[0].file_name,
            );

            const new_current_uploads_section = getCurrentSectionUploads(
                section_id,
                file_uploads_collection.pending_uploads.value,
            );

            expect(new_current_uploads_section[0].progress).toBe(100);
        });
    });

    describe("onProgressCallback", () => {
        it("should actualize progress of upload", () => {
            const { file_upload_options } = buildSectionFileUploader();

            const current_uploads_section = getCurrentSectionUploads(
                section_id,
                file_uploads_collection.pending_uploads.value,
            );

            const new_progress = current_uploads_section[0].progress + 12;
            file_upload_options.onProgressCallback(
                current_uploads_section[0].file_name,
                new_progress,
            );

            const new_current_uploads_section = getCurrentSectionUploads(
                section_id,
                file_uploads_collection.pending_uploads.value,
            );

            expect(new_current_uploads_section[0].progress).toBe(new_progress);
        });

        describe("if the file is not found in upload list", () => {
            it("should not actualize upload list state", () => {
                const { file_upload_options } = buildSectionFileUploader();

                const save_upload_files = [...file_uploads_collection.pending_uploads.value];

                file_upload_options.onProgressCallback("unknown file name", 12);

                expect(file_uploads_collection.pending_uploads.value).toEqual(save_upload_files);
            });
        });
    });
});
