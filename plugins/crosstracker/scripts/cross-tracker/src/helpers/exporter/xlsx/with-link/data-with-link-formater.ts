/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import type { Cell } from "../../../../domain/ArtifactsTable";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import { ARTIFACT_COLUMN_NAME } from "../../../../domain/ColumnName";
import type { GetColumnName } from "../../../../domain/ColumnNameGetter";
import type { RowEntry, TableDataStore } from "../../../../domain/TableDataStore";
import { buildXLSXContentCell } from "../common/content-cell-builder";

export type ContentSection = {
    headers: ReadonlyArray<TextCell>;
    rows: ReadonlyArray<ReadonlyArray<ReportCell>>;
};

export function formatDataWithLink(
    table_data: TableDataStore,
    column_name_getter: GetColumnName,
): ContentSection {
    const rows: ReportCell[][] = [];
    let row_values: ReportCell[] = [];

    const displayed_artifact_list = table_data.getRowCollection();

    const parent_row_list = displayed_artifact_list.filter((row) => row.parent_row_uuid === null);

    parent_row_list.forEach((artifact_row) => {
        row_values = [];
        row_values.concat(addRowsToXLSXContent(artifact_row, row_values));

        const linked_artifact_list_first_lvl = displayed_artifact_list.filter(
            (row) => artifact_row.row.row_uuid === row.parent_row_uuid,
        );

        if (isAParentRowWithoutLinks(linked_artifact_list_first_lvl)) {
            rows.push(row_values);
        }
        if (linked_artifact_list_first_lvl.length > 0) {
            rows.concat(
                fillXLSXRowRecursively(
                    displayed_artifact_list,
                    linked_artifact_list_first_lvl,
                    rows,
                ),
            );
        }
    });

    return {
        headers: buildXLSXHeaders(table_data, column_name_getter, rows),
        rows,
    };
}

function buildXLSXHeaders(
    table_data: TableDataStore,
    column_name_getter: GetColumnName,
    all_xlsx_rows: ReportCell[][],
): TextCell[] {
    const columns = table_data.getColumns();
    if (columns.size <= 0) {
        return [];
    }
    const first_row_column_names: TextCell[] = [];

    // We need to know which row is the longest to know how many times we need to duplicate the selected column names.
    let longest_row = 0;
    all_xlsx_rows.forEach((row) => {
        longest_row = Math.max(row.length, longest_row);
    });

    // The column @artifact is not displayed in the export
    const column_size_without_at_artifact_mandatory_column = columns.size - 1;

    // The number of columns should not change when there are linked artifacts in the data model
    const number_of_repetition_of_columns =
        longest_row / column_size_without_at_artifact_mandatory_column;

    for (let i = 0; i < number_of_repetition_of_columns; i++) {
        columns.forEach((column_name) => {
            if (column_name === ARTIFACT_COLUMN_NAME) {
                return;
            }
            first_row_column_names.push(
                new TextCell(column_name_getter.getTranslatedColumnName(column_name)),
            );
        });
    }
    return first_row_column_names;
}

/**
 *  Avoid unnecessary line in the xlsx.
 *  i.e: You have the following artifact rows:
 *   - Artifact Row A , the parent.
 *   - Artifact Row B , the linked artifact row.
 *   Without this check we would have in xlsx: [A] in one line and [A,B] in the next line
 *   With this check we will directly have in the xlsx [A, B]  (without the line [A])
 **/
function isAParentRowWithoutLinks(linked_artifact_row: RowEntry[]): boolean {
    return linked_artifact_row.length <= 0;
}

function fillCurrentXLSXRowWithParentArtifactRow(
    displayed_artifact_list: Array<RowEntry>,
    current_row_entry: RowEntry,
    parents_row_to_add_before_current_row: Array<RowEntry>,
): Array<RowEntry> {
    const linked_artifact_previous_lvl_parent_row = displayed_artifact_list.find(
        (row) =>
            current_row_entry.parent_row_uuid === row.row.row_uuid &&
            !parents_row_to_add_before_current_row.includes(row),
    );
    if (linked_artifact_previous_lvl_parent_row !== undefined) {
        parents_row_to_add_before_current_row.unshift(linked_artifact_previous_lvl_parent_row);
        return fillCurrentXLSXRowWithParentArtifactRow(
            displayed_artifact_list,
            linked_artifact_previous_lvl_parent_row,
            parents_row_to_add_before_current_row,
        );
    }
    return parents_row_to_add_before_current_row;
}

function fillXLSXRowRecursively(
    all_displayed_artifact_list: Array<RowEntry>,
    linked_artifact_list: Array<RowEntry>,
    accumulating_rows: ReportCell[][],
): ReportCell[][] {
    if (linked_artifact_list.length > 0) {
        let linked_rows: ReportCell[] = [];
        linked_artifact_list.forEach((linked_artifact_row) => {
            linked_rows = [];

            //We need to get all the parent recursively because we do not know how many parent the current artifact row has.
            const parents_row_to_add_before_current_row: RowEntry[] =
                fillCurrentXLSXRowWithParentArtifactRow(
                    all_displayed_artifact_list,
                    linked_artifact_row,
                    [],
                );

            parents_row_to_add_before_current_row.forEach((parent_row) => {
                linked_rows.concat(addRowsToXLSXContent(parent_row, linked_rows));
            });

            linked_rows.concat(addRowsToXLSXContent(linked_artifact_row, linked_rows));

            const linked_artifact_list_next_lvl = all_displayed_artifact_list.filter(
                (row) => linked_artifact_row.row.row_uuid === row.parent_row_uuid,
            );
            if (isAParentRowWithoutLinks(linked_artifact_list_next_lvl)) {
                accumulating_rows.push(linked_rows);
            }

            return fillXLSXRowRecursively(
                all_displayed_artifact_list,
                linked_artifact_list_next_lvl,
                accumulating_rows,
            );
        });
    }
    return accumulating_rows;
}

function addRowsToXLSXContent(row: RowEntry, linked_rows: ReportCell[]): ReportCell[] {
    row.row.cells.forEach((cell: Cell) => {
        linked_rows.push(buildXLSXContentCell(cell));
    });
    return linked_rows;
}
