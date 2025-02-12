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

import type { UploadError } from "./upload-file.error";

export interface OngoingUpload {
    readonly cancel: () => void;
}

export type OnGoingUploadFile = {
    file_name: string;
    progress: number;
};

export type UploadPostInformation = {
    readonly upload_url: string;
    readonly getUploadJsonPayload: (file: File) => unknown;
};

export type FileIdentifier = number | string;

export type FileUploadOptions = {
    readonly post_information: UploadPostInformation;
    onErrorCallback: (error: UploadError, file_name: string) => void;
    onSuccessCallback: (id: FileIdentifier, download_href: string, file_name: string) => void;
    onProgressCallback: (file_name: string, global_progress: number) => void;
};
