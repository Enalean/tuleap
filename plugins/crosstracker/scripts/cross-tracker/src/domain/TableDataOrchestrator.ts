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

import type { ArtifactsTableWithTotal, RetrieveArtifactsTable } from "./RetrieveArtifactsTable";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { TableDataStore } from "./TableDataStore";
import type { RetrieveArtifactLinks } from "./RetrieveArtifactLinks";
import type { ArtifactRow, ArtifactsTable } from "./ArtifactsTable";

export type TableDataOrchestrator = {
    loadTopLevelArtifacts(
        tql_query: string,
        limit: number,
        offset: number,
    ): ResultAsync<ArtifactsTableWithTotal, Fault>;
    loadForwardArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
    ): ResultAsync<ArtifactsTableWithTotal, Fault>;
    loadReverseArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
    ): ResultAsync<ArtifactsTableWithTotal, Fault>;
    loadAllForwardArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
    ): ResultAsync<ArtifactsTable, Fault>;
    loadAllReverseArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
    ): ResultAsync<ArtifactsTable, Fault>;
    closeArtifactRow(row: ArtifactRow): void;
};

export const TableDataOrchestrator = (
    artifacts_table_retriever: RetrieveArtifactsTable,
    artifacts_links_table_retriever: RetrieveArtifactLinks,
    table_data_store: TableDataStore,
): TableDataOrchestrator => {
    return {
        loadTopLevelArtifacts: (
            tql_query: string,
            limit: number,
            offset: number,
        ): ResultAsync<ArtifactsTableWithTotal, Fault> => {
            const results = artifacts_table_retriever.getSelectableQueryResult(
                tql_query,
                limit,
                offset,
            );

            return results.map((content_with_total) => {
                table_data_store.setColumns(content_with_total.table.columns);
                content_with_total.table.rows.forEach((row) =>
                    table_data_store.addEntry({ parent_row_uuid: null, row }),
                );

                return content_with_total;
            });
        },
        loadForwardArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
        ): ResultAsync<ArtifactsTableWithTotal, Fault> {
            const results = artifacts_links_table_retriever.getForwardLinks(
                row.artifact_id,
                tql_query,
            );

            return results.map((content_with_total) => {
                content_with_total.table.rows.forEach((linked_row) =>
                    table_data_store.addEntry({
                        parent_row_uuid: row.row_uuid,
                        row: linked_row,
                    }),
                );

                return content_with_total;
            });
        },
        loadReverseArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
        ): ResultAsync<ArtifactsTableWithTotal, Fault> {
            const results = artifacts_links_table_retriever.getReverseLinks(
                row.artifact_id,
                tql_query,
            );

            return results.map((content_with_total) => {
                content_with_total.table.rows.forEach((linked_row) =>
                    table_data_store.addEntry({
                        parent_row_uuid: row.row_uuid,
                        row: linked_row,
                    }),
                );

                return content_with_total;
            });
        },
        loadAllForwardArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
        ): ResultAsync<ArtifactsTable, Fault> {
            const results = artifacts_links_table_retriever.getAllForwardLinks(
                row.artifact_id,
                tql_query,
            );

            return results.map((content_with_total) => {
                content_with_total.rows.forEach((linked_row) =>
                    table_data_store.addEntry({
                        parent_row_uuid: row.row_uuid,
                        row: linked_row,
                    }),
                );

                return content_with_total;
            });
        },
        loadAllReverseArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
        ): ResultAsync<ArtifactsTable, Fault> {
            const results = artifacts_links_table_retriever.getAllReverseLinks(
                row.artifact_id,
                tql_query,
            );

            return results.map((content_with_total) => {
                content_with_total.rows.forEach((linked_row) =>
                    table_data_store.addEntry({
                        parent_row_uuid: row.row_uuid,
                        row: linked_row,
                    }),
                );

                return content_with_total;
            });
        },
        closeArtifactRow(row: ArtifactRow): void {
            table_data_store.removeEntryByParentUUID(row.row_uuid);
        },
    };
};
