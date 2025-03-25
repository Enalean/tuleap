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

import { describe, expect, it, vi } from "vitest";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync } from "neverthrow";
import { ArtifactsTableRetriever } from "./ArtifactsTableRetriever";
import type { RetrieveArtifactsTable } from "../domain/RetrieveArtifactsTable";
import { ArtifactsTableBuilder } from "./ArtifactsTableBuilder";
import { SelectableQueryContentRepresentationStub } from "../../tests/builders/SelectableQueryContentRepresentationStub";
import { ArtifactRepresentationStub } from "../../tests/builders/ArtifactRepresentationStub";
import { uri } from "@tuleap/fetch-result";

describe(`ArtifactsTableRetriever`, () => {
    describe(`getSelectableQueryResult()`, () => {
        const limit = 30;
        const offset = 30;
        const widget_id = 15;
        const query_id = "0194dfd6-a489-703b-aabd-9d473212d908";
        const tql_query = `SELECT start_date WHERE @title = "forevouched"`;

        const getRetriever = (): RetrieveArtifactsTable => {
            return ArtifactsTableRetriever(widget_id, ArtifactsTableBuilder());
        };

        it(`will send the given expert query to the REST API
            and will return them organized in ArtifactsTable
            with the total number of artifacts`, async () => {
            const date_field_name = "start_date";
            const total = 45;
            const first_date_value = "2022-04-27T11:54:15+07:00";
            const query_content = SelectableQueryContentRepresentationStub.build(
                [{ type: "date", name: date_field_name }],
                [
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: first_date_value, with_time: true },
                    }),
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: null, with_time: false },
                    }),
                ],
            );
            const getResponse = vi.spyOn(fetch_result, "getResponse").mockReturnValue(
                okAsync({
                    headers: new Headers({ "X-PAGINATION-SIZE": String(total) }),
                    json: () => Promise.resolve(query_content),
                } as Response),
            );

            const result = await getRetriever().getSelectableQueryResult(tql_query, limit, offset);

            expect(getResponse).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/crosstracker_query/content`,
                {
                    params: {
                        limit,
                        offset,
                        query: JSON.stringify({
                            widget_id,
                            tql_query,
                        }),
                    },
                },
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value.total).toBe(total);
            const table = result.value.table;
            expect(table.columns).toHaveLength(2);
            expect(table.rows).toHaveLength(2);
        });
        it(`will return organized in ArtifactsTable
            with the total number of artifacts
            from an already existing query and not from a saved query`, async () => {
            const date_field_name = "start_date";
            const total = 45;
            const first_date_value = "2022-04-27T11:54:15+07:00";
            const widget_content = SelectableQueryContentRepresentationStub.build(
                [{ type: "date", name: date_field_name }],
                [
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: first_date_value, with_time: true },
                    }),
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: null, with_time: false },
                    }),
                ],
            );
            const getResponse = vi.spyOn(fetch_result, "getResponse").mockReturnValue(
                okAsync({
                    headers: new Headers({ "X-PAGINATION-SIZE": String(total) }),
                    json: () => Promise.resolve(widget_content),
                } as Response),
            );

            const result = await getRetriever().getSelectableQueryContent(query_id, limit, offset);

            expect(getResponse).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/crosstracker_query/${query_id}/content`,
                {
                    params: {
                        limit,
                        offset,
                    },
                },
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value.total).toBe(total);
            const table = result.value.table;
            expect(table.columns).toHaveLength(2);
            expect(table.rows).toHaveLength(2);
        });
        it(`will return organized in ArtifactsTable
            from an already existing query and not from a saved query`, async () => {
            const date_field_name = "start_date";
            const first_date_value = "2022-04-27T11:54:15+07:00";
            const widget_content = SelectableQueryContentRepresentationStub.build(
                [{ type: "date", name: date_field_name }],
                [
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: first_date_value, with_time: true },
                    }),
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: null, with_time: false },
                    }),
                ],
            );
            const end_date_field_name = "end_date";
            const second_date_value = "2025-04-10T11:54:15+07:00";
            const query_content_second_page = SelectableQueryContentRepresentationStub.build(
                [{ type: "date", name: end_date_field_name }],
                [
                    ArtifactRepresentationStub.build({
                        [end_date_field_name]: { value: null, with_time: false },
                    }),
                    ArtifactRepresentationStub.build({
                        [end_date_field_name]: { value: null, with_time: false },
                    }),
                    ArtifactRepresentationStub.build({
                        [end_date_field_name]: { value: second_date_value, with_time: true },
                    }),
                ],
            );
            const getAllJSON = vi
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync([widget_content, query_content_second_page]));

            const result = await getRetriever().getSelectableQueryFullResult(query_id);

            expect(getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/crosstracker_query/${query_id}/content`,
                {
                    params: {
                        limit: 50,
                    },
                },
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            const table = result.value;
            expect(table).toHaveLength(2);
        });
    });
});
