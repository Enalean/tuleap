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

import type {
    FileUploadOptions,
    MaxSizeUploadExceededError,
    UploadError,
    OnGoingUploadFile,
} from "@tuleap/prose-mirror-editor";
import { ref } from "vue";
import type { Ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import type { AttachmentFile } from "@/composables/useAttachmentFile";

export type UseUploadFileType = {
    file_upload_options: FileUploadOptions;
    error_message: Ref<string | null>;
    progress: Ref<number>;
    is_in_progress: Ref<boolean>;
    resetProgressCallback: () => void;
};

export function useUploadFile(
    upload_url: string,
    add_attachment_to_waiting_list: AttachmentFile["addAttachmentToWaitingList"],
): UseUploadFileType {
    const error_message: Ref<string | null> = ref(null);
    const upload_max_size = strictInject(UPLOAD_MAX_SIZE);
    const progress = ref(0);
    const is_in_progress = ref(false);
    const upload_files: Ref<Map<number, OnGoingUploadFile>> = ref(new Map());

    const onErrorCallback = (error: UploadError | MaxSizeUploadExceededError): void => {
        error_message.value = error.message;
    };
    const onSuccessCallback = (id: number, download_href: string): void => {
        add_attachment_to_waiting_list({ id, upload_url: download_href });
        error_message.value = "";
    };

    const onProgressCallback = (global_progress: number): void => {
        is_in_progress.value = true;
        progress.value = global_progress;
    };
    const resetProgressCallback = (): void => {
        progress.value = 0;
        is_in_progress.value = false;
    };

    const file_upload_options: FileUploadOptions = {
        upload_url: upload_url,
        max_size_upload: upload_max_size,
        upload_files: upload_files.value,
        onErrorCallback,
        onSuccessCallback,
        onProgressCallback,
    };
    return { file_upload_options, error_message, progress, is_in_progress, resetProgressCallback };
}
