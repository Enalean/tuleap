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

import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import { getReportArtifacts } from "../rest-querier";
import type { GlobalExportProperties } from "../type";

export type FormattedCell = TextCell | NumberCell | EmptyCell;

export class TextCell {
    readonly type = "text";

    constructor(readonly value: string) {}
}

export class NumberCell {
    readonly type = "number";

    constructor(readonly value: number) {}
}

export class EmptyCell {
    readonly type = "empty";
}

export interface ReportSection {
    readonly headers?: ReadonlyArray<TextCell>;
    readonly rows?: ReadonlyArray<ReadonlyArray<FormattedCell>>;
}

export async function formatData(
    global_properties: GlobalExportProperties
): Promise<ReportSection> {
    const report_artifacts: ArtifactResponse[] = await getReportArtifacts(
        global_properties.report_id,
        true
    );

    if (report_artifacts.length === 0) {
        return {};
    }

    const report_field_columns: Array<TextCell> = [];
    const artifact_rows: Array<Array<FormattedCell>> = [];
    let first_row_processed = false;
    let artifact_value_rows: Array<FormattedCell> = [];

    for (const artifact of report_artifacts) {
        artifact_value_rows = [];
        for (const field_value of artifact.values) {
            if (!first_row_processed) {
                report_field_columns.push(new TextCell(field_value.label));
            }

            switch (field_value.type) {
                case "string":
                    artifact_value_rows.push(new TextCell(field_value.value ?? ""));
                    break;
                case "int":
                case "float":
                case "aid":
                    artifact_value_rows.push(getCellFromPossibleNumber(field_value.value));
                    break;
                case "computed": {
                    let value;
                    if (field_value.is_autocomputed) {
                        value = field_value.value;
                    } else {
                        value = field_value.manual_value;
                    }
                    artifact_value_rows.push(getCellFromPossibleNumber(value));
                    break;
                }
                default:
                    artifact_value_rows.push(new EmptyCell());
            }
        }
        artifact_rows.push(artifact_value_rows);
        first_row_processed = true;
    }

    return {
        headers: report_field_columns,
        rows: artifact_rows,
    };
}

function getCellFromPossibleNumber(n: number | null): NumberCell | EmptyCell {
    if (n === null) {
        return new EmptyCell();
    }

    return new NumberCell(n);
}
