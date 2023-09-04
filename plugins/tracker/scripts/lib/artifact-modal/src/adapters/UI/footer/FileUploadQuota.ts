/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { define, html } from "hybrids";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import { getMaxAllowedUploadSizeText } from "../../../gettext-catalog";
import type { FileUploadQuotaControllerType } from "../../../domain/common/FileUploadQuotaController";

export interface FileUploadQuota {
    readonly controller: FileUploadQuotaControllerType;
    max_upload_size_in_bytes: number;
}
export type HostElement = FileUploadQuota & HTMLElement;

export const FileUploadQuota = define<FileUploadQuota>({
    tag: "tuleap-artifact-modal-file-upload-quota",
    controller: {
        async set(host, controller: FileUploadQuotaControllerType) {
            host.max_upload_size_in_bytes = await controller.getMaxAllowedUploadSizeInBytes();
            return controller;
        },
    },
    max_upload_size_in_bytes: 0,
    content: (host) => {
        if (host.max_upload_size_in_bytes === 0) {
            return html``;
        }
        const quota_message = sprintf(
            getMaxAllowedUploadSizeText(),
            prettyKibibytes(host.max_upload_size_in_bytes),
        );
        return html`<div class="tlp-text-info">${quota_message}</div>`;
    },
});
