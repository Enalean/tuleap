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

import type { ArtifactsTable } from "../../../../domain/ArtifactsTable";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import { ARTIFACT_COLUMN_NAME } from "../../../../domain/ColumnName";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { GetColumnName } from "../../../../domain/ColumnNameGetter";
import { buildXLSXContentCell } from "../common/content-cell-builder";

export type ContentSection = {
    headers: ReadonlyArray<TextCell>;
    rows: ReadonlyArray<ReadonlyArray<ReportCell>>;
};

export function formatData(
    artifact_table: ReadonlyArray<ArtifactsTable>,
    column_name_getter: GetColumnName,
): ContentSection {
    const first_row_column_names: TextCell[] = [];
    artifact_table[0].columns.forEach((column_name) => {
        if (column_name === ARTIFACT_COLUMN_NAME) {
            return;
        }
        first_row_column_names.push(
            new TextCell(column_name_getter.getTranslatedColumnName(column_name)),
        );
    });

    const rows: ReportCell[][] = [];
    let row_values: ReportCell[] = [];
    artifact_table.forEach((artifact) => {
        for (const row of artifact.rows) {
            row_values = [];
            for (const column of artifact.columns) {
                if (column === ARTIFACT_COLUMN_NAME) {
                    continue;
                }
                const current_artifact_cell = row.cells.get(column);
                row_values.push(buildXLSXContentCell(current_artifact_cell));
            }
            rows.push(row_values);
        }
    });
    return {
        headers: first_row_column_names,
        rows,
    };
}
