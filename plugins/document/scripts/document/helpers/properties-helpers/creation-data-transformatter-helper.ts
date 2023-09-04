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

import { getStatusProperty } from "./hardcoded-properties-mapping-helper";
import { updateStatusProperty } from "./value-transformer/status-property-helper";
import { formatDateValue } from "./value-transformer/date-property-helper";
import {
    formatPropertyListValue,
    formatPropertyListMultipleValue,
} from "./value-transformer/list-value-helper";
import type { DefaultFileItem, Folder, Item, Property } from "../../type";

export function transformStatusPropertyForItemCreation(
    document_to_create: Item | DefaultFileItem,
    parent: Folder,
    is_status_property_used: boolean,
): void {
    if (!is_status_property_used) {
        return;
    }

    const property = getStatusProperty(parent.properties);
    if (!property) {
        return;
    }
    updateStatusProperty(property, document_to_create);
}

export function transformCustomPropertiesForItemCreation(
    properties: Array<Property> | null,
): Array<Property> {
    if (properties === null || properties.length === 0) {
        return [];
    }

    const formatted_properties: Array<Property> = [];
    properties.forEach((parent_property) => {
        const formatted_property: Property = {
            short_name: parent_property.short_name,
            type: parent_property.type,
            name: parent_property.name,
            is_multiple_value_allowed: parent_property.is_multiple_value_allowed,
            is_required: parent_property.is_required,
            description: parent_property.description,
            is_used: parent_property.is_used,
            list_value: null,
            value: null,
            allowed_list_values: null,
        };

        switch (parent_property.type) {
            case "date":
                formatted_property.value = formatDateValue(parent_property.value);
                formatted_properties.push(formatted_property);
                break;
            case "text":
            case "string":
                formatted_property.value = parent_property.value;
                formatted_properties.push(formatted_property);
                break;
            case "list":
                if (parent_property.is_multiple_value_allowed) {
                    formatted_property.list_value =
                        formatPropertyListMultipleValue(parent_property);
                    formatted_properties.push(formatted_property);
                } else {
                    formatted_property.value = formatPropertyListValue(parent_property);
                    formatted_properties.push(formatted_property);
                }
                break;
            default:
                break;
        }
    });

    return formatted_properties;
}
