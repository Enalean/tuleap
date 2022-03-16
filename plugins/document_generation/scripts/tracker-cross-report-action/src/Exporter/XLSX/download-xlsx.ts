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

import { utils, writeFile } from "xlsx";
import type { GlobalExportProperties, ReportFieldColumn } from "../../type";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";

export function downloadXLSX(
    global_properties: GlobalExportProperties,
    report_field_columns: ReportFieldColumn[],
    report_artifacts: ArtifactResponse[]
): void {
    const book = utils.book_new();
    const sheet = utils.aoa_to_sheet(
        buildContent(global_properties, report_field_columns, report_artifacts)
    );
    utils.book_append_sheet(book, sheet);
    writeFile(
        book,
        global_properties.tracker_name + "-" + global_properties.report_name + ".xlsx",
        {
            bookSST: true,
        }
    );
}

function buildContent(
    global_properties: GlobalExportProperties,
    report_field_columns: ReportFieldColumn[],
    report_artifacts: ArtifactResponse[]
): Array<Array<string | number>> {
    const content: Array<Array<string | number>> = [["report_id", global_properties.report_id]];
    const report_columns_label: string[] = [];
    for (const column of report_field_columns) {
        report_columns_label.push(column.field_label);
    }
    content.push(report_columns_label);
    for (const artifact of report_artifacts) {
        content.push(["artifact_id", artifact.id]);
    }

    return content;
}
