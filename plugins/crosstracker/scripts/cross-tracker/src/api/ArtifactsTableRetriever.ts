/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { decodeJSON, getResponse, uri } from "@tuleap/fetch-result";
import type { SelectableReportContentRepresentation } from "./cross-tracker-rest-api-types";
import type {
    ArtifactsTableWithTotal,
    RetrieveArtifactsTable,
} from "../domain/RetrieveArtifactsTable";
import type { ArtifactsTableBuilder } from "./ArtifactsTableBuilder";

export const ArtifactsTableRetriever = (
    table_builder: ArtifactsTableBuilder,
    report_id: number,
): RetrieveArtifactsTable => {
    return {
        getSelectableQueryResult(
            tracker_ids,
            expert_query,
            limit,
            offset,
        ): ResultAsync<ArtifactsTableWithTotal, Fault> {
            return getResponse(uri`/api/v1/cross_tracker_reports/${report_id}/content`, {
                params: {
                    limit,
                    offset,
                    query: JSON.stringify({ trackers_id: tracker_ids, expert_query }),
                },
            }).andThen((response) => {
                const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
                return decodeJSON<SelectableReportContentRepresentation>(response).map((report) => {
                    return { table: table_builder.mapReportToArtifactsTable(report), total };
                });
            });
        },

        getSelectableReportContent(limit, offset): ResultAsync<ArtifactsTableWithTotal, Fault> {
            return getResponse(uri`/api/v1/cross_tracker_reports/${report_id}/content`, {
                params: {
                    limit,
                    offset,
                },
            }).andThen((response) => {
                const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
                return decodeJSON<SelectableReportContentRepresentation>(response).map((report) => {
                    return { table: table_builder.mapReportToArtifactsTable(report), total };
                });
            });
        },
    };
};
