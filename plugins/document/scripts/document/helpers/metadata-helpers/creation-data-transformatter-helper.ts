/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { getStatusMetadata } from "./hardcoded-metadata-mapping-helper";
import { updateItemMetadata } from "./value-transformer/status-metadata-helper";
import { formatDateValue } from "./value-transformer/date-metadata-helper";
import {
    formatMetadataListValue,
    formatMetadataMultipleValue,
} from "./value-transformer/list-value-helper";
import type { Folder, Item } from "../../type";
import type { Metadata } from "../../store/metadata/module";

export function transformItemMetadataForCreation(
    document_to_create: Item,
    parent: Folder,
    is_item_status_metadata_used: boolean
): void {
    if (!is_item_status_metadata_used) {
        return;
    }

    const metadata = getStatusMetadata(parent.metadata);
    if (!metadata) {
        return;
    }
    updateItemMetadata(metadata, document_to_create);
}

export function transformCustomMetadataForItemCreation(
    parents_metadata: Array<Metadata>
): Array<Metadata> {
    if (parents_metadata.length === 0) {
        return [];
    }

    const formatted_metadata_list: Array<Metadata> = [];
    parents_metadata.forEach((parent_metadata) => {
        const formatted_metadata: Metadata = {
            short_name: parent_metadata.short_name,
            type: parent_metadata.type,
            name: parent_metadata.name,
            is_multiple_value_allowed: parent_metadata.is_multiple_value_allowed,
            is_required: parent_metadata.is_required,
            description: parent_metadata.description,
            is_used: parent_metadata.is_used,
            list_value: null,
            value: null,
            allowed_list_values: null,
        };

        switch (parent_metadata.type) {
            case "date":
                formatted_metadata.value = formatDateValue(parent_metadata.value);
                formatted_metadata_list.push(formatted_metadata);
                break;
            case "text":
            case "string":
                formatted_metadata.value = parent_metadata.value;
                formatted_metadata_list.push(formatted_metadata);
                break;
            case "list":
                if (parent_metadata.is_multiple_value_allowed) {
                    formatted_metadata.list_value = formatMetadataMultipleValue(parent_metadata);
                    formatted_metadata_list.push(formatted_metadata);
                } else {
                    formatted_metadata.value = formatMetadataListValue(parent_metadata);
                    formatted_metadata_list.push(formatted_metadata);
                }
                break;
            default:
                break;
        }
    });

    return formatted_metadata_list;
}
