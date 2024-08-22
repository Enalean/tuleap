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

import { ref } from "vue";
import type { UseUploadFileType } from "@/composables/useUploadFile";
import type { OnGoingUploadFile } from "@tuleap/prose-mirror-editor";

const noop = (): void => {};

export const UploadFileStub = {
    uploadNotInProgress: (): UseUploadFileType => ({
        file_upload_options: {
            upload_url: "upload_url",
            max_size_upload: 123,
            upload_files: new Map(),
            onErrorCallback: noop,
            onSuccessCallback: noop,
            onProgressCallback: noop,
        },
        error_message: ref(""),
        progress: ref(0),
        is_in_progress: ref(false),
        resetProgressCallback: noop,
    }),

    uploadInProgress: (): UseUploadFileType => ({
        file_upload_options: {
            upload_url: "https://upload_url",
            max_size_upload: 12345678,
            upload_files: new Map<number, OnGoingUploadFile>([
                [
                    1,
                    {
                        file_name: "file_1.jpg",
                        progress: 45,
                    },
                ],
            ]),
            onErrorCallback: noop,
            onSuccessCallback: noop,
            onProgressCallback: noop,
        },
        error_message: ref(""),
        progress: ref(45),
        is_in_progress: ref(true),
        resetProgressCallback: noop,
    }),
};
