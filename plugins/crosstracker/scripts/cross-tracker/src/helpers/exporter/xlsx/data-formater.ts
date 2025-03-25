/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { ArtifactsTable, Cell } from "../../../domain/ArtifactsTable";
import {
    STATIC_LIST_CELL,
    TEXT_CELL,
    PROJECT_CELL,
    NUMERIC_CELL,
    USER_CELL,
    USER_LIST_CELL,
    USER_GROUP_LIST_CELL,
    TRACKER_CELL,
    DATE_CELL,
    PRETTY_TITLE_CELL,
} from "../../../domain/ArtifactsTable";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import { ARTIFACT_COLUMN_NAME } from "../../../domain/ColumnName";
import { EmptyCell, TextCell, NumberCell, HTMLCell, DateCell } from "@tuleap/plugin-docgen-xlsx";
import type { GetColumnName } from "../../../domain/ColumnNameGetter";

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

function buildXLSXContentCell(current_artifact_cell: Cell | undefined): ReportCell {
    if (!current_artifact_cell) {
        return new EmptyCell();
    }
    if (current_artifact_cell.type === NUMERIC_CELL) {
        return current_artifact_cell.value.mapOr((value) => {
            return new NumberCell(value);
        }, new EmptyCell());
    }
    if (current_artifact_cell.type === PROJECT_CELL) {
        const project_name =
            current_artifact_cell.icon !== ""
                ? current_artifact_cell.icon + " " + current_artifact_cell.name
                : current_artifact_cell.name;
        return new TextCell(project_name);
    }
    if (current_artifact_cell.type === TEXT_CELL) {
        return new HTMLCell(current_artifact_cell.value);
    }
    if (current_artifact_cell.type === USER_CELL) {
        return new TextCell(current_artifact_cell.display_name);
    }
    if (current_artifact_cell.type === TRACKER_CELL) {
        return new TextCell(current_artifact_cell.name);
    }
    if (current_artifact_cell.type === USER_LIST_CELL) {
        let user_list = "";
        current_artifact_cell.value.forEach((user, index) => {
            user_list +=
                index >= current_artifact_cell.value.length - 1
                    ? user.display_name
                    : user.display_name + ", ";
        });
        return new TextCell(user_list);
    }
    if (current_artifact_cell.type === USER_GROUP_LIST_CELL) {
        let user_group_list = "";
        current_artifact_cell.value.forEach((user_group, index) => {
            user_group_list +=
                index >= current_artifact_cell.value.length - 1
                    ? user_group.label
                    : user_group.label + ", ";
        });
        return new TextCell(user_group_list);
    }
    if (current_artifact_cell.type === DATE_CELL) {
        return current_artifact_cell.value.mapOr(
            (date: string) => new DateCell(date),
            new EmptyCell(),
        );
    }
    if (current_artifact_cell.type === STATIC_LIST_CELL) {
        let values = "";
        current_artifact_cell.value.forEach((value, index) => {
            values +=
                index >= current_artifact_cell.value.length - 1 ? value.label : value.label + ", ";
        });
        return new TextCell(values);
    }
    if (current_artifact_cell.type === PRETTY_TITLE_CELL) {
        return new TextCell(
            current_artifact_cell.tracker_name +
                "#" +
                current_artifact_cell.artifact_id +
                " " +
                current_artifact_cell.title,
        );
    }
    return new EmptyCell();
}
