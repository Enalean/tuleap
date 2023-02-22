/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { isThereAtLeastOneFileField } from "./file-field-detector";
import { getFileUploadRules } from "../../rest/rest-service";

const file_upload_rules = {
    // All units are in bytes
    disk_quota: 0,
    disk_usage: 0,
    max_chunk_size: 0,
};

export { updateFileUploadRulesWhenNeeded, file_upload_rules };

function updateFileUploadRulesWhenNeeded(field_values) {
    if (isThereAtLeastOneFileField(field_values)) {
        return getFileUploadRules().then((data) => {
            Object.assign(file_upload_rules, data);
        });
    }

    return Promise.resolve();
}
