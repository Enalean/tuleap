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
import type { RetrieveArtifactsTable } from "../../domain/RetrieveArtifactsTable";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { ok, okAsync } from "neverthrow";
import type { ContentSection } from "./xlsx/without-link/data-formater";
import { formatData } from "./xlsx/without-link/data-formater";
import type { GetColumnName } from "../../domain/ColumnNameGetter";
import type { Query } from "../../type";
import { formatDataWithLink } from "./xlsx/with-link/data-with-link-formater";
import type { TableDataStore } from "../../domain/TableDataStore";

export function downloadXLSXDocument(
    artifact_table_retriever: RetrieveArtifactsTable,
    query: Query,
    column_name_getter: GetColumnName,
    download_document: (formated_data: ContentSection, title: string) => void,
): ResultAsync<null, Fault> {
    return artifact_table_retriever.getSelectableQueryFullResult(query.id).andThen((table) => {
        const formated_data = formatData(table, column_name_getter);
        download_document(formated_data, query.title);
        return ok(null);
    });
}

export function downloadXLSXWithLinkDocument(
    table_data: TableDataStore,
    column_name_getter: GetColumnName,
    query: Query,
    download_document: (formated_data: ContentSection, title: string) => void,
): ResultAsync<null, Fault> {
    const formated_data = formatDataWithLink(table_data, column_name_getter);
    download_document(formated_data, query.title);
    return okAsync(null);
}
