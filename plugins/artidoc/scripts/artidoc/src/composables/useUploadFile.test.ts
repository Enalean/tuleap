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
import { mockFileList } from "@/helpers/mock-file-list";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import { NOTIFICATION_STORE } from "@/stores/notification-injection-key";
import { UploadError } from "@tuleap/file-upload";
import type { GetText } from "@tuleap/gettext";
import type { OnGoingUploadFileWithId, UploadFileStoreType } from "@/stores/useUploadFileStore";
import type { UseNotificationsStoreType } from "@/stores/useNotificationsStore";
import type { ManageSectionAttachmentFiles } from "@/sections/SectionAttachmentFilesManager";
import { UploadFileStoreStub } from "@/helpers/stubs/UploadFileStoreStub";
import { NotificationsSub } from "@/helpers/stubs/NotificationsStub";
import { SectionAttachmentFilesManagerStub } from "@/sections/stubs/SectionAttachmentFilesManagerStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";

const gettext_provider = {
    gettext: (msgid: string) => msgid,
} as unknown as GetText;

function getCurrentSectionUploads(
    section_id: string,
    store_data: OnGoingUploadFileWithId[],
): OnGoingUploadFileWithId[] {
    return store_data.filter((upload) => upload.section_id === section_id);
}
describe("useUploadFile", () => {
    let mocked_upload_data: UploadFileStoreType,
        mocked_notifications_data: UseNotificationsStoreType,
        manage_section_attachments: ManageSectionAttachmentFiles;

    const section_id: string =
        UploadFileStoreStub.uploadInProgress().pending_uploads.value[0].section_id;

    beforeEach(() => {
        mocked_upload_data = UploadFileStoreStub.uploadInProgress();
        mocked_notifications_data = NotificationsSub.withMessages();
        manage_section_attachments = SectionAttachmentFilesManagerStub.forSection(
            FreetextSectionFactory.create(),
        );
        mockStrictInject([
            [UPLOAD_MAX_SIZE, 222],
            [UPLOAD_FILE_STORE, mocked_upload_data],
            [NOTIFICATION_STORE, mocked_notifications_data],
        ]);
    });

    describe("error_message", () => {
        it("should return the upload error message", () => {
            const mocked_add_notification = vi.fn();
            mockStrictInject([
                [UPLOAD_MAX_SIZE, 222],
                [UPLOAD_FILE_STORE, mocked_upload_data],
                [
                    NOTIFICATION_STORE,
                    { ...mocked_notifications_data, addNotification: mocked_add_notification },
                ],
            ]);

            const { file_upload_options } = useUploadFile(section_id, manage_section_attachments);

            file_upload_options.onErrorCallback(new UploadError(gettext_provider), "file_name");

            expect(mocked_add_notification).toHaveBeenCalledWith({
                message: "An error occurred during upload",
                type: "danger",
            });
        });
        it("should delete the current file pending upload", () => {
            const mocked_delete_upload = vi.fn();
            mockStrictInject([
                [UPLOAD_MAX_SIZE, 222],
                [UPLOAD_FILE_STORE, { ...mocked_upload_data, deleteUpload: mocked_delete_upload }],
                [NOTIFICATION_STORE, mocked_notifications_data],
            ]);

            const { file_upload_options } = useUploadFile(section_id, manage_section_attachments);

            const current_file = mocked_upload_data.pending_uploads.value[0];
            file_upload_options.onErrorCallback(
                new UploadError(gettext_provider),
                current_file.file_name,
            );

            expect(mocked_delete_upload).toHaveBeenCalledWith(current_file.file_id);
        });
    });

    describe("resetProgressCallback", () => {
        it("should reset progress", () => {
            const cancelSectionUploadsMock = vi.fn();
            mockStrictInject([
                [UPLOAD_MAX_SIZE, 222],
                [
                    UPLOAD_FILE_STORE,
                    { ...mocked_upload_data, cancelSectionUploads: cancelSectionUploadsMock },
                ],
                [NOTIFICATION_STORE, mocked_notifications_data],
            ]);

            const { resetProgressCallback } = useUploadFile(section_id, manage_section_attachments);

            resetProgressCallback();

            expect(cancelSectionUploadsMock).toHaveBeenCalledOnce();
        });
    });

    describe("onStartUploadCallback", () => {
        it("should add the current upload to the store pending uploads", () => {
            const addPendingUploadMock = vi.fn();
            mockStrictInject([
                [UPLOAD_MAX_SIZE, 222],
                [
                    UPLOAD_FILE_STORE,
                    { ...mocked_upload_data, addPendingUpload: addPendingUploadMock },
                ],
                [NOTIFICATION_STORE, mocked_notifications_data],
            ]);

            const { file_upload_options } = useUploadFile(section_id, manage_section_attachments);

            const list: FileList = mockFileList([new File(["123"], "file_1")]);
            file_upload_options.onStartUploadCallback(list);

            expect(addPendingUploadMock).toHaveBeenNthCalledWith(1, "file_1", section_id);
        });
    });

    describe("onSuccessCallback", () => {
        it("should add the current upload to the store pending uploads", () => {
            const add_attachment_to_waiting_list_mock = vi.spyOn(
                manage_section_attachments,
                "addAttachmentToWaitingList",
            );
            const { file_upload_options } = useUploadFile(section_id, manage_section_attachments);

            file_upload_options.onSuccessCallback(123, "download_href", "file_name");
            expect(add_attachment_to_waiting_list_mock).toHaveBeenCalledWith({
                id: 123,
                upload_url: "download_href",
            });
        });
        it("should actualize progress of upload to 100%", () => {
            const { file_upload_options } = useUploadFile(section_id, manage_section_attachments);

            const current_uploads_section = getCurrentSectionUploads(
                section_id,
                mocked_upload_data.pending_uploads.value,
            );
            expect(current_uploads_section[0].progress).toBe(45);

            file_upload_options.onSuccessCallback(
                123,
                "download_href",
                mocked_upload_data.pending_uploads.value[0].file_name,
            );

            const new_current_uploads_section = getCurrentSectionUploads(
                section_id,
                mocked_upload_data.pending_uploads.value,
            );

            expect(new_current_uploads_section[0].progress).toBe(100);
        });
    });

    describe("onProgressCallback", () => {
        it("should actualize progress of upload", () => {
            const { file_upload_options } = useUploadFile(section_id, manage_section_attachments);

            const current_uploads_section = getCurrentSectionUploads(
                section_id,
                mocked_upload_data.pending_uploads.value,
            );

            const new_progress = current_uploads_section[0].progress + 12;
            file_upload_options.onProgressCallback(
                current_uploads_section[0].file_name,
                new_progress,
            );

            const new_current_uploads_section = getCurrentSectionUploads(
                section_id,
                mocked_upload_data.pending_uploads.value,
            );

            expect(new_current_uploads_section[0].progress).toBe(new_progress);
        });
        describe("if the file is not found in upload list", () => {
            it("should not actualize upload list state", () => {
                const { file_upload_options } = useUploadFile(
                    section_id,
                    manage_section_attachments,
                );

                const save_upload_files = [...mocked_upload_data.pending_uploads.value];

                file_upload_options.onProgressCallback("unknown file name", 12);

                expect(mocked_upload_data.pending_uploads.value).toEqual(save_upload_files);
            });
        });
    });
});
