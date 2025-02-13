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

import { ref, type Ref } from "vue";
import type { OnGoingUploadFile } from "@tuleap/file-upload";
import { v4 as uuidv4 } from "uuid";

export type FileUploadsCollection = {
    pending_uploads: Ref<OnGoingUploadFileWithId[]>;
    deleteUpload: (file_id: string) => void;
    cancelSectionUploads: (section_id: string) => void;
    addPendingUpload: (file_name: string, section_id: string) => void;
};

export type OnGoingUploadFileWithId = OnGoingUploadFile & {
    file_id: string;
    section_id: string;
    error_message?: string | null | undefined;
};

export function getFileUploadsCollection(): FileUploadsCollection {
    const pending_uploads: Ref<OnGoingUploadFileWithId[]> = ref([]);

    const deleteUpload = (file_id: string): void => {
        const index = pending_uploads.value.findIndex(
            (upload: OnGoingUploadFileWithId) => upload.file_id === file_id,
        );
        if (index !== -1) {
            pending_uploads.value.splice(index, 1);
        }
    };

    const cancelSectionUploads = (section_id: string): void => {
        pending_uploads.value = pending_uploads.value.filter(
            (upload) => upload.section_id !== section_id,
        );
    };

    const addPendingUpload = (file_name: string, section_id: string): void => {
        pending_uploads.value.push({
            file_id: uuidv4(),
            file_name,
            progress: 0,
            section_id,
        });
    };

    return {
        pending_uploads,
        addPendingUpload,
        cancelSectionUploads,
        deleteUpload,
    };
}
