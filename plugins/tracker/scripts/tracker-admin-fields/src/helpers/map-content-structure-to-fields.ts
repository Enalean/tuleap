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
import type { ColumnWrapper, ElementWithChildren } from "../type";
import { CONTAINER_COLUMN, CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";

export function mapContentStructureToFields(
    content: StructureFormat["content"],
    fields: TrackerResponseNoInstance["fields"],
): ElementWithChildren {
    if (content === null) {
        return {
            children: [],
        };
    }

    const children: ElementWithChildren["children"] = [];
    let column_wrapper: ColumnWrapper | null = null;

    for (const child of content) {
        const field = fields.find((element: StructureFields) => element.field_id === child.id);
        if (field === undefined) {
            continue;
        }

        if (field.type === CONTAINER_COLUMN) {
            if (column_wrapper === null) {
                column_wrapper = {
                    columns: [],
                };
                children.push(column_wrapper);
            }
            column_wrapper.columns.push({
                field,
                ...mapContentStructureToFields(child.content, fields),
            });
        } else {
            column_wrapper = null;
            children.push({
                field,
                ...(field.type === CONTAINER_FIELDSET
                    ? mapContentStructureToFields(child.content, fields)
                    : {}),
            });
        }
    }

    return {
        children,
    };
}
