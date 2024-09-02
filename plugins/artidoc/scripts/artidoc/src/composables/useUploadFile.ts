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
    FileUploadOptions,
    MaxSizeUploadExceededError,
    UploadError,
    OnGoingUploadFile,
} from "@tuleap/prose-mirror-editor";
import { computed, ref } from "vue";
import type { Ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import type { OnGoingUploadFileWithId } from "@/stores/useUploadFileStore";

export type UseUploadFileType = {
    file_upload_options: FileUploadOptions;
    error_message: Ref<string | null>;
    is_in_progress: Ref<boolean>;
    resetProgressCallback: () => void;
};

export function useUploadFile(
    section_id: string,
    upload_url: string,
    add_attachment_to_waiting_list: AttachmentFile["addAttachmentToWaitingList"],
): UseUploadFileType {
    const upload_max_size = strictInject(UPLOAD_MAX_SIZE);
    const { addPendingUpload, pending_uploads, cancelSectionUploads } =
        strictInject(UPLOAD_FILE_STORE);

    const error_message: Ref<string | null> = ref(null);
    const is_in_progress = computed(() => {
        return (
            pending_uploads.value.filter(
                (upload: OnGoingUploadFileWithId) => upload.section_id === section_id,
            ).length > 0
        );
    });

    const onStartUploadCallback = (file_name: string): OnGoingUploadFile[] => {
        addPendingUpload(file_name, section_id);
        return pending_uploads.value;
    };
    const onErrorCallback = (error: UploadError | MaxSizeUploadExceededError): void => {
        error_message.value = error.message;
    };
    const onSuccessCallback = (id: number, download_href: string): void => {
        add_attachment_to_waiting_list({ id, upload_url: download_href });
        error_message.value = "";
    };

    const onProgressCallback = (file_name: string, global_progress: number): void => {
        const file_index = pending_uploads.value.findIndex(
            (upload: OnGoingUploadFileWithId) => upload.file_name === file_name,
        );
        if (file_index >= 0) {
            pending_uploads.value[file_index] = {
                ...pending_uploads.value[file_index],
                file_name,
                progress: global_progress,
            };
        }
    };
    const resetProgressCallback = (): void => {
        cancelSectionUploads(section_id);
    };

    const file_upload_options: FileUploadOptions = {
        upload_url: upload_url,
        max_size_upload: upload_max_size,
        onStartUploadCallback,
        onErrorCallback,
        onSuccessCallback,
        onProgressCallback,
    };
    return {
        file_upload_options,
        error_message,
        is_in_progress,
        resetProgressCallback,
    };
}
