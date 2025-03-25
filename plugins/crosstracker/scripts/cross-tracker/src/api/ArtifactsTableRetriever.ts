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
import { decodeJSON, getResponse, uri, getAllJSON } from "@tuleap/fetch-result";
import type { SelectableQueryContentRepresentation } from "./cross-tracker-rest-api-types";
import type {
    ArtifactsTableWithTotal,
    RetrieveArtifactsTable,
} from "../domain/RetrieveArtifactsTable";
import type { ArtifactsTableBuilder } from "./ArtifactsTableBuilder";
import type { ArtifactsTable } from "../domain/ArtifactsTable";

export const ArtifactsTableRetriever = (
    widget_id: number,
    table_builder: ArtifactsTableBuilder,
): RetrieveArtifactsTable => {
    return {
        getSelectableQueryResult(
            tql_query,
            limit,
            offset,
        ): ResultAsync<ArtifactsTableWithTotal, Fault> {
            return getResponse(uri`/api/v1/crosstracker_query/content`, {
                params: {
                    limit,
                    offset,
                    query: JSON.stringify({
                        widget_id,
                        tql_query,
                    }),
                },
            }).andThen((response) => {
                const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
                return decodeJSON<SelectableQueryContentRepresentation>(response).map(
                    (query_content) => {
                        return {
                            table: table_builder.mapQueryContentToArtifactsTable(query_content),
                            total,
                        };
                    },
                );
            });
        },

        getSelectableQueryContent(
            query_id,
            limit,
            offset,
        ): ResultAsync<ArtifactsTableWithTotal, Fault> {
            return getResponse(uri`/api/v1/crosstracker_query/${query_id}/content`, {
                params: {
                    limit,
                    offset,
                },
            }).andThen((response) => {
                const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
                return decodeJSON<SelectableQueryContentRepresentation>(response).map(
                    (query_content) => {
                        return {
                            table: table_builder.mapQueryContentToArtifactsTable(query_content),
                            total,
                        };
                    },
                );
            });
        },

        getSelectableQueryFullResult(query_id): ResultAsync<readonly ArtifactsTable[], Fault> {
            return getAllJSON<SelectableQueryContentRepresentation>(
                uri`/api/v1/crosstracker_query/${query_id}/content`,
                {
                    params: {
                        limit: 50,
                    },
                },
            ).map((query_content) => {
                return query_content.map((table) =>
                    table_builder.mapQueryContentToArtifactsTable(table),
                );
            });
        },
    };
};
