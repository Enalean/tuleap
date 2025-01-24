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

import type { GetText } from "@tuleap/gettext";
import type { UploadError } from "@tuleap/file-upload";

export class GenericUploadError extends Error implements UploadError {
    constructor(gettext_provider: GetText) {
        super();
        this.name = "UploadError";
        this.message = gettext_provider.gettext("An error occurred during upload");
    }
}

export class MaxSizeUploadExceededError extends Error implements UploadError {
    public max_size_upload: number;
    constructor(max_size_upload: number, gettext_provider: GetText) {
        super();
        this.name = "MaxSizeUploadExceededError";
        this.max_size_upload = max_size_upload;
        this.message = gettext_provider.gettext("Max upload size exceeded");
    }
}

export class NoUploadError extends Error implements UploadError {
    constructor(gettext_provider: GetText) {
        super();
        this.name = "NoUploadError";
        this.message = gettext_provider.gettext("You are not allowed to upload file here");
    }
}

export class InvalidFileUploadError extends Error implements UploadError {
    constructor(gettext_provider: GetText) {
        super();
        this.name = "InvalidFileUploadError";
        this.message = gettext_provider.gettext("File type is invalid, you can only upload images");
    }
}
