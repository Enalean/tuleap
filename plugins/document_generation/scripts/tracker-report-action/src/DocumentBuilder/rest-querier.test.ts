/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import * as tlp from "@tuleap/tlp-fetch";
import { getReportArtifacts } from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import type { TrackerWithProjectAndColor } from "@tuleap/plugin-tracker-rest-api-types";

vi.mock("@tuleap/tlp-fetch");

describe("API querier", () => {
    describe("getReportArtifacts", () => {
        it("Given a report id, Then it will get the artifact matching the report, and the report in session if needed", async () => {
            const report_id = 101;
            const report_has_changed = true;
            const tlpRecursiveGet = vi.spyOn(tlp, "recursiveGet");

            const artifacts_report_response: ArtifactResponse[] = [
                {
                    id: 74,
                    xref: "bug #74",
                    title: null,
                    tracker: { id: 102 } as TrackerWithProjectAndColor,
                    html_url: "/plugins/tracker/?aid=74",
                    status: "irrelevant",
                    is_open: true,
                    values: [
                        {
                            field_id: 2,
                            type: "date",
                            label: "My Date",
                            value: "2021-07-30T15:56:09+02:00",
                        },
                    ],
                },
            ];
            mockFetchSuccess(tlpRecursiveGet, {
                return_json: {
                    artifacts_report_response,
                },
            });

            await getReportArtifacts(report_id, report_has_changed);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/tracker_reports/101/artifacts", {
                params: { limit: 50, values: "all", with_unsaved_changes: true },
            });
        });
    });
});
