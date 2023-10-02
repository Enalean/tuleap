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

import { getStatusFromMapping, getStatusProperty } from "./hardcoded-properties-mapping-helper";
import { updateStatusProperty } from "./value-transformer/status-property-helper";
import { formatDateValue } from "./value-transformer/date-property-helper";
import {
    assertListIsOnlyMultipleValue,
    formatPropertyListValue,
    formatPropertyListMultipleValue,
} from "./value-transformer/list-value-helper";
import type { Folder, Item, Property } from "../../type";

export function transformFolderPropertiesForRecursionAtUpdate(
    item: Folder,
    is_status_property_used: boolean,
): Folder {
    const folder_to_update = JSON.parse(JSON.stringify(item));

    if (!folder_to_update.properties || !is_status_property_used) {
        folder_to_update.status = {
            value: "none",
            recursion: "none",
        };

        return folder_to_update;
    }

    const property = getStatusProperty(folder_to_update.properties);
    folder_to_update.status = {
        value:
            !property || !property.list_value || !assertListIsOnlyMultipleValue(property.list_value)
                ? "none"
                : getStatusFromMapping(property.list_value[0].id),
        recursion: "none",
    };

    return folder_to_update;
}

export function transformDocumentPropertiesForUpdate(
    document_to_update: Item,
    is_status_property_used: boolean,
): void {
    if (!is_status_property_used || !document_to_update.properties) {
        return;
    }

    const property = getStatusProperty(document_to_update.properties);
    if (!property) {
        return;
    }
    updateStatusProperty(property, document_to_update);
}

export function transformCustomPropertiesForItemUpdate(parent_properties: Array<Property>): void {
    parent_properties.forEach((parent_property) => {
        switch (parent_property.type) {
            case "date":
                parent_property.value = formatDateValue(parent_property.value);
                break;
            case "text":
            case "string":
                break;
            case "list":
                if (parent_property.is_multiple_value_allowed) {
                    parent_property.list_value = formatPropertyListMultipleValue(parent_property);
                    parent_property.value = null;
                } else {
                    parent_property.value = formatPropertyListValue(parent_property);
                    parent_property.list_value = null;
                }
                break;
            default:
                break;
        }
    });
}

export function formatCustomPropertiesForFolderUpdate(
    item_to_update: Folder,
    properties_to_update: Array<string>,
    recursion_option: string,
): Folder {
    const updated_item = structuredClone(item_to_update);
    updated_item.properties.forEach((item_properties) => {
        if (properties_to_update.find((short_name) => short_name === item_properties.short_name)) {
            item_properties.recursion = recursion_option;
        } else {
            item_properties.recursion = "none";
        }
    });

    return updated_item;
}
