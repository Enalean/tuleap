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
import { getLinkedArtifacts, getReportArtifacts, getTrackerReports } from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import type { TrackerReportResponse } from "@tuleap/plugin-tracker-rest-api-types/src";

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
    describe("getLinkedArtifacts", () => {
        it("Given an artifact id and a link type, Then it will get the linked artifacts with this type", async () => {
            const artifact_id = 101;
            const artifact_link_type = "_is_child";
            const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");

            const artifacts_report_response: ArtifactResponse[] = [
                {
                    id: 74,
                } as ArtifactResponse,
            ];
            mockFetchSuccess(tlpRecursiveGet, {
                return_json: {
                    collection: artifacts_report_response,
                },
            });

            await getLinkedArtifacts(artifact_id, artifact_link_type);

            expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/artifacts/101/linked_artifacts", {
                params: { limit: 10, direction: "forward", nature: "_is_child" },
            });
        });
    });
    describe("getTrackerReports", () => {
        it("retrieves tracker reports", async () => {
            const tracker_id = 123;
            const recursive_get_spy = jest.spyOn(tlp, "recursiveGet");

            const reports_response: TrackerReportResponse[] = [
                {
                    id: 741,
                    label: "Report label",
                    is_public: true,
                },
            ];
            mockFetchSuccess(recursive_get_spy, {
                return_json: {
                    collection: reports_response,
                },
            });

            await getTrackerReports(tracker_id);

            expect(recursive_get_spy).toHaveBeenCalledWith(
                `/api/v1/trackers/${tracker_id}/tracker_reports`
            );
        });
    });
});
