/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

import * as tlp from "@tuleap/tlp-fetch";
import { getReportArtifacts } from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";

jest.mock("tlp");

describe("API querier", () => {
    describe("getReportArtifacts", () => {
        it("Given a report id, Then it will get the artifact matching the report, and the report in session if needed", async () => {
            const report_id = 101;
            const report_has_changed = true;
            const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");

            const artifacts_report_response: ArtifactResponse[] = [
                {
                    id: 74,
                } as ArtifactResponse,
            ];
            mockFetchSuccess(tlpRecursiveGet, {
                return_json: {
                    artifacts_report_response,
                },
            });

            await getReportArtifacts(report_id, report_has_changed);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/tracker_reports/101/artifacts", {
                params: { limit: 50, values: "from_table_renderer", with_unsaved_changes: true },
            });
        });
    });
});
