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

import type { FileUploadQuotaPresenter } from "./FileUploadQuotaPresenter";
import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import {
    getEmptyDiskQuotaMessage,
    getQuotaUsageMessage,
    getUsedQuotaMessage,
} from "../../../gettext-catalog";
import type { FileUploadQuotaControllerType } from "./FileUploadQuotaController";

export interface FileUploadQuota {
    readonly controller: FileUploadQuotaControllerType;
    presenter: FileUploadQuotaPresenter;
}
export type HostElement = FileUploadQuota & HTMLElement;

const getNoDiskUsageTemplate = (host: FileUploadQuota): UpdateFunction<FileUploadQuota> => {
    const quota_message = sprintf(
        getEmptyDiskQuotaMessage(),
        prettyKibibytes(host.presenter.disk_quota_in_bytes)
    );
    return html`
        <div class="tlp-text-info">${quota_message}</div>
    `;
};

const getQuotaPercentageTemplate = (host: FileUploadQuota): UpdateFunction<FileUploadQuota> => {
    const quota_message = sprintf(getQuotaUsageMessage(), {
        usage: prettyKibibytes(host.presenter.disk_usage_in_bytes),
        quota: prettyKibibytes(host.presenter.disk_quota_in_bytes),
    });
    return html`
        <div class="tlp-property">
            <label class="tlp-label">
                <i class="angular-artifact-modal-disk-usage-file-upload-icon far fa-file-alt"></i>
                ${getUsedQuotaMessage()}
            </label>
            <div class="angular-artifact-modal-disk-usage-progress-info">
                <div class="angular-artifact-modal-disk-usage-progress-bar">
                    <div class="angular-artifact-modal-disk-usage-progress-bar-progression"></div>
                </div>
                <span class="tlp-text-info">${quota_message}</span>
            </div>
        </div>
    `;
};

const getTemplate = (host: FileUploadQuota): UpdateFunction<FileUploadQuota> =>
    host.presenter.disk_usage_in_bytes > 0
        ? getQuotaPercentageTemplate(host)
        : getNoDiskUsageTemplate(host);

export const FileUploadQuota = define<FileUploadQuota>({
    tag: "tuleap-artifact-modal-file-upload-quota",
    controller: {
        set(host, controller: FileUploadQuotaControllerType) {
            host.presenter = controller.displayUploadQuota();
            host.style.setProperty(
                "--disk-usage-progress",
                `${host.presenter.disk_usage_percentage}%`
            );
        },
    },
    presenter: undefined,
    content: getTemplate,
});
