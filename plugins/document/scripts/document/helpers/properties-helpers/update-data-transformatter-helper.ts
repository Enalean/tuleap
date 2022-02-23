/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { getStatusFromMapping, getStatusMetadata } from "./hardcoded-metadata-mapping-helper";
import { updateItemMetadata } from "./value-transformer/status-metadata-helper";
import { formatDateValue } from "./value-transformer/date-metadata-helper";
import {
    assertListIsOnlyMultipleValue,
    formatMetadataListValue,
    formatMetadataMultipleValue,
} from "./value-transformer/list-value-helper";
import type { Folder, Item } from "../../type";
import type { Metadata } from "../../store/metadata/module";

export function transformFolderMetadataForRecursionAtUpdate(item: Folder): Folder {
    const folder_to_update = JSON.parse(JSON.stringify(item));

    if (!folder_to_update.metadata) {
        folder_to_update.status = {
            value: "none",
            recursion: "none",
        };

        return folder_to_update;
    }

    const metadata = getStatusMetadata(folder_to_update.metadata);
    folder_to_update.status = {
        value:
            !metadata || !metadata.list_value || !assertListIsOnlyMultipleValue(metadata.list_value)
                ? "none"
                : getStatusFromMapping(metadata.list_value[0].id),
        recursion: "none",
    };

    return folder_to_update;
}

export function transformDocumentMetadataForUpdate(
    document_to_update: Item,
    is_item_status_metadata_used: boolean
): void {
    if (!is_item_status_metadata_used) {
        return;
    }

    const metadata = getStatusMetadata(document_to_update.metadata);
    if (!metadata) {
        return;
    }
    updateItemMetadata(metadata, document_to_update);
}

export function transformCustomMetadataForItemUpdate(parent_metadata: Array<Metadata>): void {
    parent_metadata.forEach((parent_metadata) => {
        switch (parent_metadata.type) {
            case "date":
                parent_metadata.value = formatDateValue(parent_metadata.value);
                break;
            case "text":
            case "string":
                break;
            case "list":
                if (parent_metadata.is_multiple_value_allowed) {
                    parent_metadata.list_value = formatMetadataMultipleValue(parent_metadata);
                    parent_metadata.value = null;
                } else {
                    parent_metadata.value = formatMetadataListValue(parent_metadata);
                    parent_metadata.list_value = null;
                }
                break;
            default:
                break;
        }
    });
}

export function formatCustomMetadataForFolderUpdate(
    item_to_update: Folder,
    metadata_list_to_update: Array<string>,
    recursion_option: string
): void {
    item_to_update.metadata.forEach((item_metadata) => {
        if (metadata_list_to_update.find((short_name) => short_name === item_metadata.short_name)) {
            item_metadata.recursion = recursion_option;
        } else {
            item_metadata.recursion = "none";
        }
    });
}
