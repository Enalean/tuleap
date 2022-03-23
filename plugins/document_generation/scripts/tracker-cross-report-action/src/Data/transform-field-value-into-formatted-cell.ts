/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { EmptyCell, NumberCell, TextCell, HTMLCell, DateCell } from "./data-formator";
import type { FormattedCell } from "./data-formator";
import type { ArtifactReportResponseFieldValue } from "@tuleap/plugin-docgen-docx";

export function transformFieldValueIntoAFormattedCell(
    field_value: Readonly<ArtifactReportResponseFieldValue>
): FormattedCell {
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
