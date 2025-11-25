/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import {
    type StructureFields,
    type StructureFormat,
    type TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";

interface FieldWithContent {
    readonly field: StructureFields;
    readonly content: StructureFormat["content"];
}

export function mapContentStructureToFields(
    content: StructureFormat["content"],
    fields: TrackerResponseNoInstance["fields"],
): FieldWithContent[] {
    return (content || []).reduce(
        (fields_with_content: FieldWithContent[], child: StructureFormat) => {
            const field = fields.find((element: StructureFields) => element.field_id === child.id);
            if (field !== undefined) {
                fields_with_content.push({
                    field,
                    content: child.content,
                });
            }

            return fields_with_content;
        },
        [],
    );
}
