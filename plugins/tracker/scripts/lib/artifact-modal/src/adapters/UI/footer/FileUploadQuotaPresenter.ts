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

import type { UserTemporaryFileQuota } from "../../../domain/fields/file-field/UserTemporaryFileQuota";

export interface FileUploadQuotaPresenter extends UserTemporaryFileQuota {
    disk_usage_percentage: number;
}

export function computePercentage(quota: UserTemporaryFileQuota): number {
    if (quota.disk_quota_in_bytes <= 0) {
        return 0;
    }
    const float = (quota.disk_usage_in_bytes / quota.disk_quota_in_bytes) * 100 * 100;
    return Math.floor(float) / 100;
}

export const FileUploadQuotaPresenter = {
    fromQuota: (quota: UserTemporaryFileQuota): FileUploadQuotaPresenter => ({
        disk_usage_in_bytes: quota.disk_usage_in_bytes,
        disk_quota_in_bytes: quota.disk_quota_in_bytes,
        disk_usage_percentage: computePercentage(quota),
    }),
};
