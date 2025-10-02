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

import type { RowEntry } from "../../src/domain/TableDataStore";
import { TableDataStore } from "../../src/domain/TableDataStore";
import type { ColumnName } from "../../src/domain/ColumnName";
import { ARTIFACT_COLUMN_NAME } from "../../src/domain/ColumnName";

type TableDataStoreTestBuilder = {
    build(): TableDataStore;
    withColumns(column: ColumnName, ...other_columns: ReadonlyArray<ColumnName>): void;
    withEntries(entry: RowEntry, ...other_entries: ReadonlyArray<RowEntry>): void;
};

export const TableDataStoreTestBuilder = (): TableDataStoreTestBuilder => {
    const table_data: TableDataStore = TableDataStore();

    const build = (): TableDataStore => {
        return table_data;
    };
    const withColumns = (column: ColumnName, ...other_columns: ReadonlyArray<ColumnName>): void => {
        table_data.setColumns(
            new Set<ColumnName>([ARTIFACT_COLUMN_NAME, column, ...other_columns]),
        );
    };

    const withEntries = (entry: RowEntry, ...other_entries: ReadonlyArray<RowEntry>): void => {
        [entry, ...other_entries].forEach((new_entry) => {
            table_data.addEntry(new_entry);
        });
    };

    return {
        build,
        withColumns,
        withEntries,
    };
};
