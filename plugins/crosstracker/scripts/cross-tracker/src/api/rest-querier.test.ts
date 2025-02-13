/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { getReports, updateReport } from "./rest-querier";

describe("rest-querier", () => {
    describe("getReport()", () => {
        it(`will query the REST API and return the report`, async () => {
            const report = {
                expert_query: '@title = "bla"',
                title: "TQL title",
                description: '@title = "bla"',
            };
            const getJSON = vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync([report]));
            const report_id = 16;

            const result = await getReports(report_id);

            expect(getJSON).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}`,
            );
            if (!result.isOk()) {
                throw Error("Expected an ok");
            }
            expect(result.value[0].expert_query).toBe(report.expert_query);
            expect(result.value[0].title).toBe(report.title);
            expect(result.value[0].description).toBe(report.description);
        });
    });

    describe("updateReport()", () => {
        it(`will send the given tracker ids and expert query to be saved by the REST API
                and will return the report from the response`, async () => {
            const expert_query =
                "Select  @id, @project.name from @project = MY_PROJECTS() where @id > 2";
            const report = {
                expert_query,
                title: " My TQL query",
                description: "My description",
            };
            const putJSON = vi.spyOn(fetch_result, "putJSON").mockReturnValue(okAsync(report));
            const report_id = 59;

            const result = await updateReport(report_id, expert_query);

            expect(putJSON).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}`,
                expect.any(Object),
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value.expert_query).toBe(report.expert_query);
        });
    });
});
