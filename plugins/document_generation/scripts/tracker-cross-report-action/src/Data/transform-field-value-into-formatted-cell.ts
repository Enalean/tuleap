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

import { EmptyCell, NumberCell, TextCell, HTMLCell, DateCell } from "@tuleap/plugin-docgen-xlsx";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import type { ArtifactReportResponseFieldValue } from "@tuleap/plugin-docgen-docx";

export function transformFieldValueIntoAFormattedCell(
    field_value: Readonly<ArtifactReportResponseFieldValue>,
): ReportCell {
    switch (field_value.type) {
        case "string":
            return new TextCell(field_value.value ?? "");
        case "text":
            if (field_value.format === "text") {
                return new TextCell(field_value.value ?? "");
            }

            return new HTMLCell(field_value.value ?? "");
        case "int":
        case "float":
        case "aid":
        case "atid":
        case "priority":
            return getCellFromPossibleNumber(field_value.value);
        case "computed": {
            let value;
            if (field_value.is_autocomputed) {
                value = field_value.value;
            } else {
                value = field_value.manual_value;
            }
            return getCellFromPossibleNumber(value);
        }
        case "date":
        case "lud":
        case "subon":
            if (field_value.value === null) {
                return new EmptyCell();
            }
            return new DateCell(new Date(field_value.value));
        case "sb":
        case "msb":
        case "rb":
        case "cb": {
            if (field_value.values === null || field_value.values.length === 0) {
                return new EmptyCell();
            }

            const list_values_labels: string[] = [];
            for (const list_value of field_value.values) {
                if ("display_name" in list_value && list_value.id !== null) {
                    list_values_labels.push(list_value.display_name);
                } else if ("label" in list_value) {
                    list_values_labels.push(list_value.label);
                }
            }
            return new TextCell(list_values_labels.join(", "));
        }
        case "tbl": {
            if (
                field_value.bind_value_objects === null ||
                field_value.bind_value_objects.length === 0
            ) {
                return new EmptyCell();
            }

            const formatted_open_values: string[] = [];
            for (const open_list_value of field_value.bind_value_objects) {
                if ("display_name" in open_list_value) {
                    formatted_open_values.push(open_list_value.display_name);
                } else if ("label" in open_list_value) {
                    formatted_open_values.push(open_list_value.label);
                }
            }
            return new TextCell(formatted_open_values.join(", "));
        }
        case "subby":
        case "luby": {
            if (field_value.value === null) {
                return new EmptyCell();
            }

            return new TextCell(field_value.value.real_name ?? "");
        }
        default:
            return new EmptyCell();
    }
}

function getCellFromPossibleNumber(n: number | null): NumberCell | EmptyCell {
    if (n === null) {
        return new EmptyCell();
    }

    return new NumberCell(n);
}
