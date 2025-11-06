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

import type { RetrieveArtifactsTable } from "./RetrieveArtifactsTable";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { RowEntry, TableDataStore } from "./TableDataStore";
import type { RetrieveArtifactLinks } from "./RetrieveArtifactLinks";
import type { ArtifactLinkDirection, ArtifactRow, ArtifactsTable } from "./ArtifactsTable";

export type TableDataOrchestrator = {
    loadTopLevelArtifacts(
        tql_query: string,
        limit: number,
        offset: number,
        success_callback: () => void,
        error_callback: (fault: Fault) => void,
    ): Promise<{ result: OrchestratorOperationResult; total: number }>;
    loadForwardArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
        error_callback: (fault: Fault) => void,
    ): Promise<OrchestratorOperationResult>;
    loadReverseArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
        error_callback: (fault: Fault) => void,
    ): Promise<OrchestratorOperationResult>;
    loadAllForwardArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
    ): ResultAsync<OrchestratorOperationResult, Fault>;
    loadAllReverseArtifactLinks(
        row: ArtifactRow,
        tql_query: string,
    ): ResultAsync<OrchestratorOperationResult, Fault>;
    closeArtifactRow(row: ArtifactRow): OrchestratorOperationResult;
};

export type ArtifactLinkLoadError = {
    row_uuid: string;
    error: string;
    direction: ArtifactLinkDirection;
};

export type OrchestratorOperationResult = {
    row_collection: RowEntry[];
    columns: ArtifactsTable["columns"];
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
            success_callback: () => void,
            error_callback: (fault: Fault) => void,
        ): Promise<{ result: OrchestratorOperationResult; total: number }> => {
            table_data_store.resetStore();
            return artifacts_table_retriever
                .getSelectableQueryResult(tql_query, limit, offset)
                .match(
                    (content_with_total) => {
                        table_data_store.setColumns(content_with_total.table.columns);
                        content_with_total.table.rows.forEach((row) =>
                            table_data_store.addEntry({ parent_row_uuid: null, row }),
                        );
                        success_callback();
                        return {
                            result: {
                                row_collection: table_data_store.getRowCollection(),
                                columns: table_data_store.getColumns(),
                            },
                            total: content_with_total.total,
                        };
                    },
                    (fault) => {
                        error_callback(fault);

                        return {
                            result: {
                                row_collection: new Array<RowEntry>(),
                                columns: new Set(),
                            },
                            total: 0,
                        };
                    },
                );
        },
        loadForwardArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
            error_callback: (fault: Fault) => void,
        ): Promise<OrchestratorOperationResult> {
            return artifacts_links_table_retriever
                .getForwardLinks(row.artifact_id, tql_query)
                .match(
                    (content_with_total) => {
                        content_with_total.table.rows.forEach((linked_row) =>
                            table_data_store.addEntry({
                                parent_row_uuid: row.row_uuid,
                                row: linked_row,
                            }),
                        );

                        return {
                            row_collection: table_data_store.getRowCollection(),
                            columns: table_data_store.getColumns(),
                        };
                    },
                    (fault: Fault) => {
                        error_callback(fault);
                        return {
                            row_collection: table_data_store.getRowCollection(),
                            columns: table_data_store.getColumns(),
                        };
                    },
                );
        },
        loadReverseArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
            error_callback: (fault: Fault) => void,
        ): Promise<OrchestratorOperationResult> {
            return artifacts_links_table_retriever
                .getReverseLinks(row.artifact_id, tql_query)
                .match(
                    (content_with_total) => {
                        content_with_total.table.rows.forEach((linked_row) =>
                            table_data_store.addEntry({
                                parent_row_uuid: row.row_uuid,
                                row: linked_row,
                            }),
                        );

                        return {
                            row_collection: table_data_store.getRowCollection(),
                            columns: table_data_store.getColumns(),
                        };
                    },
                    (fault: Fault) => {
                        error_callback(fault);
                        return {
                            row_collection: table_data_store.getRowCollection(),
                            columns: table_data_store.getColumns(),
                        };
                    },
                );
        },
        loadAllForwardArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
        ): ResultAsync<OrchestratorOperationResult, Fault> {
            const results = artifacts_links_table_retriever.getAllForwardLinks(
                row.artifact_id,
                tql_query,
            );

            return results.map((content_with_total) => {
                content_with_total.rows.forEach((linked_row) => {
                    table_data_store.addEntry({
                        parent_row_uuid: row.row_uuid,
                        row: linked_row,
                    });
                });

                return {
                    row_collection: table_data_store.getRowCollection(),
                    columns: table_data_store.getColumns(),
                };
            });
        },
        loadAllReverseArtifactLinks(
            row: ArtifactRow,
            tql_query: string,
        ): ResultAsync<OrchestratorOperationResult, Fault> {
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

                return {
                    row_collection: table_data_store.getRowCollection(),
                    columns: table_data_store.getColumns(),
                };
            });
        },
        closeArtifactRow(row: ArtifactRow): OrchestratorOperationResult {
            table_data_store.removeEntryByParentUUID(row.row_uuid);
            return {
                row_collection: table_data_store.getRowCollection(),
                columns: table_data_store.getColumns(),
            };
        },
    };
};
