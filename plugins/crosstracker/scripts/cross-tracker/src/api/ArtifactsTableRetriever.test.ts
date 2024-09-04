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
import { SelectableReportContentRepresentationStub } from "../../tests/builders/SelectableReportContentRepresentationStub";
import { ArtifactRepresentationStub } from "../../tests/builders/ArtifactRepresentationStub";
import { EXPERT_MODE } from "./cross-tracker-rest-api-types";

describe(`ArtifactsTableRetriever`, () => {
    describe(`getSelectableQueryResult()`, () => {
        const limit = 30;
        const offset = 30;
        const report_id = 583;
        const tracker_ids = [78, 518, 937];
        const expert_query = `SELECT start_date WHERE @title = "forevouched"`;
        const expert_mode = true;

        const getRetriever = (): RetrieveArtifactsTable => {
            return ArtifactsTableRetriever(ArtifactsTableBuilder(), report_id);
        };

        it(`will send the given tracker ids and expert query to the REST API
            and will return them organized in ArtifactsTable
            with the total number of artifacts`, async () => {
            const date_field_name = "start_date";
            const total = 45;
            const first_date_value = "2022-04-27T11:54:15+07:00";
            const report_content = SelectableReportContentRepresentationStub.build(
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
                    json: () => Promise.resolve(report_content),
                } as Response),
            );

            const result = await getRetriever().getSelectableQueryResult(
                tracker_ids,
                expert_query,
                expert_mode,
                limit,
                offset,
            );

            expect(getResponse).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}/content`,
                {
                    params: {
                        limit,
                        offset,
                        report_mode: EXPERT_MODE,
                        query: JSON.stringify({
                            trackers_id: tracker_ids,
                            expert_query,
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
            from an already existing report and not from a saved query`, async () => {
            const date_field_name = "start_date";
            const total = 45;
            const first_date_value = "2022-04-27T11:54:15+07:00";
            const report_content = SelectableReportContentRepresentationStub.build(
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
                    json: () => Promise.resolve(report_content),
                } as Response),
            );

            const result = await getRetriever().getSelectableReportContent(limit, offset);

            expect(getResponse).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}/content`,
                {
                    params: {
                        limit,
                        offset,
                        report_mode: EXPERT_MODE,
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
    });
});
