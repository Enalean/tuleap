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
import { ok } from "neverthrow";
import type { ReportSection } from "./xlsx/data-formater";
import { formatData } from "./xlsx/data-formater";
import type { GetColumnName } from "../../domain/ColumnNameGetter";

export function downloadXLSXDocument(
    artifact_table_retriever: RetrieveArtifactsTable,
    report_id: number,
    query_id: string,
    column_name_getter: GetColumnName,
    download_document: (formated_data: ReportSection, report_id: number) => void,
): ResultAsync<null, Fault> {
    return artifact_table_retriever.getSelectableFullReport(query_id).andThen((table) => {
        const formated_data = formatData(table, column_name_getter);
        download_document(formated_data, report_id);
        return ok(null);
    });
}
