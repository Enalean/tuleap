/**
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

import type { HeadersSection, ReportSection } from "../../Data/data-formator";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import { utils } from "xlsx";

const RANGE_SEPARATOR = ":";
const STARTING_COLUMN = "A";
const STARTING_ROW = 2;

export function generateAutofilterRange(formatted_data: ReportSection): string {
    if (
        formatted_data.headers === undefined ||
        formatted_data.artifacts_rows === undefined ||
        formatted_data.headers.reports_fields_labels.length <= 0
    ) {
        return "";
    }

    const all_headers: HeadersSection = formatted_data.headers;
    const all_artifacts_rows: ReadonlyArray<ReadonlyArray<ReportCell>> =
        formatted_data.artifacts_rows;

    return (
        getAutofilterStartingRange() +
        RANGE_SEPARATOR +
        getAutofilterEndingRange(all_headers, all_artifacts_rows)
    );
}

function getAutofilterStartingRange(): string {
    return STARTING_COLUMN + STARTING_ROW.toString();
}

function getAutofilterEndingRange(
    all_headers: HeadersSection,
    all_artifacts_rows: ReadonlyArray<ReadonlyArray<ReportCell>>,
): string {
    const ending_column: string = utils.encode_col(all_headers.reports_fields_labels.length - 1);
    const ending_row: number = all_artifacts_rows.length + STARTING_ROW;
    return ending_column + ending_row.toString();
}
