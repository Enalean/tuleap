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
import { transformFieldValueIntoAFormattedCell } from "./transform-field-value-into-formatted-cell";
import type { ArtifactReportResponseFieldValue } from "@tuleap/plugin-docgen-docx";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";

export interface ReportSection {
    readonly headers?: ReadonlyArray<TextCell>;
    readonly rows?: ReadonlyArray<ReadonlyArray<ReportCell>>;
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
    const artifact_rows: Array<Array<ReportCell>> = [];
    let first_row_processed = false;
    let artifact_value_rows: Array<ReportCell> = [];

    for (const artifact of report_artifacts) {
        artifact_value_rows = [];
        for (const field_value of artifact.values) {
            if (!isFieldTakenIntoAccount(field_value)) {
                continue;
            }

            if (!first_row_processed) {
                report_field_columns.push(new TextCell(field_value.label));
            }

            artifact_value_rows.push(transformFieldValueIntoAFormattedCell(field_value));
        }
        artifact_rows.push(artifact_value_rows);
        first_row_processed = true;
    }

    return {
        headers: report_field_columns,
        rows: artifact_rows,
    };
}

function isFieldTakenIntoAccount(field_value: ArtifactReportResponseFieldValue): boolean {
    return (
        field_value.type !== "art_link" &&
        field_value.type !== "file" &&
        field_value.type !== "cross" &&
        field_value.type !== "perm" &&
        field_value.type !== "ttmstepdef"
    );
}
