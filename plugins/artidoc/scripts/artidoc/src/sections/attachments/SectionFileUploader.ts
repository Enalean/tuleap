/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.CustomUploadFilesMap
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import type {
    FileIdentifier,
    FileUploadOptions,
    UploadError,
    OnGoingUploadFile,
} from "@tuleap/file-upload";
import type { ManageSectionAttachmentFiles } from "@/sections/attachments/SectionAttachmentFilesManager";
import type {
    FileUploadsCollection,
    OnGoingUploadFileWithId,
} from "@/sections/attachments/FileUploadsCollection";
import type { NotificationsCollection } from "@/sections/notifications/NotificationsCollection";

export type UploadSectionFile = {
    file_upload_options: FileUploadOptions & {
        max_size_upload: number;
        onStartUploadCallback: (files: FileList) => OnGoingUploadFile[];
    };
    resetProgressCallback: () => void;
};

export function getSectionFileUploader(
    section_id: string,
    manage_section_attachments: ManageSectionAttachmentFiles,
    file_uploads_collection: FileUploadsCollection,
    notifications_collection: NotificationsCollection,
    upload_max_size: number,
): UploadSectionFile {
    const onStartUploadCallback = (files: FileList): OnGoingUploadFile[] => {
        for (const file of files) {
            file_uploads_collection.addPendingUpload(file.name, section_id);
        }
        return file_uploads_collection.pending_uploads.value;
    };
    const onErrorCallback = (error: UploadError, file_name: string): void => {
        notifications_collection.addNotification({ message: error.message, type: "danger" });
        const file_to_delete = file_uploads_collection.pending_uploads.value.find(
            (upload) => upload.file_name === file_name && upload.section_id === section_id,
        );
        if (file_to_delete) {
            file_uploads_collection.deleteUpload(file_to_delete.file_id);
        }
    };
    const updateProgress = (file_name: string, new_progress: number): boolean => {
        const file_index = file_uploads_collection.pending_uploads.value.findIndex(
            (upload: OnGoingUploadFileWithId) => upload.file_name === file_name,
        );
        if (file_index >= 0) {
            file_uploads_collection.pending_uploads.value[file_index] = {
                ...file_uploads_collection.pending_uploads.value[file_index],
                file_name,
                progress: new_progress,
            };
            return true;
        }
        return false;
    };
    const onSuccessCallback = (
        id: FileIdentifier,
        download_href: string,
        file_name: string,
    ): void => {
        updateProgress(file_name, 100);
        manage_section_attachments.addAttachmentToWaitingList({ id, upload_url: download_href });
    };
    const onProgressCallback = (file_name: string, global_progress: number): void => {
        updateProgress(file_name, global_progress);
    };
    const resetProgressCallback = (): void => {
        file_uploads_collection.cancelSectionUploads(section_id);
    };

    return {
        file_upload_options: {
            post_information: manage_section_attachments.getPostInformation(),
            max_size_upload: upload_max_size,
            onStartUploadCallback,
            onErrorCallback,
            onSuccessCallback,
            onProgressCallback,
        },
        resetProgressCallback,
    };
}
