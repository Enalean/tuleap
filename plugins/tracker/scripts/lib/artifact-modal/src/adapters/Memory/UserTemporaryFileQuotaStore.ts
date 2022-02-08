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

import type { RetrieveUserTemporaryFileQuota } from "../../domain/fields/file-field/RetrieveUserTemporaryFileQuota";
import type { UserTemporaryFileQuota } from "../../domain/fields/file-field/UserTemporaryFileQuota";
import { file_upload_rules } from "../../fields/file-field/file-upload-rules-state";

type UserTemporaryFileQuotaStoreType = RetrieveUserTemporaryFileQuota;

export const UserTemporaryFileQuotaStore = (): UserTemporaryFileQuotaStoreType => {
    return {
        getUserTemporaryFileQuota: (): UserTemporaryFileQuota => {
            const upload_rules = file_upload_rules;
            return {
                disk_quota_in_bytes: upload_rules.disk_quota,
                disk_usage_in_bytes: upload_rules.disk_usage,
            };
        },
    };
};
