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

/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import type {
    TrackerDefinition,
    TestExecutionResponse,
    ArtifactReportResponse,
} from "./artifacts-retriever";
import {
    getReportArtifacts,
    getTestManagementExecution,
    getTrackerDefinition,
} from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

jest.mock("tlp");

describe("API querier", () => {
    describe("getTrackerDefinition", () => {
        it("Given a tracker id, Then it will get the tracker definition", async () => {
            const tracker_id = 101;
            const tlpGet = jest.spyOn(tlp, "get");

            const tracker_definition_response: TrackerDefinition = {
                fields: [{ field_id: 2, type: "date", is_time_displayed: false }],
                structure: [
                    {
                        id: 4,
                        content: [{ id: 2, content: null }],
                    },
                ],
            };
            mockFetchSuccess(tlpGet, {
                return_json: {
                    tracker_definition_response,
                },
            });

            await getTrackerDefinition(tracker_id);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/trackers/101");
        });
    });
    describe("getReportArtifacts", () => {
        it("Given a report id, Then it will get the artifact matching the report, and the report in session if needed", async () => {
            const report_id = 101;
            const report_has_changed = true;
            const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");

            const artifacts_report_response: ArtifactReportResponse[] = [
                {
                    id: 74,
                    title: null,
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
    describe("getTestManagementExecution", () => {
        it("Given an artifact id, Then it will get the testmanagement execution", async () => {
            const artifact_id = 101;
            const tlpGet = jest.spyOn(tlp, "get");

            const testmanagement_execution_response: TestExecutionResponse = {
                definition: {
                    description: "",
                    description_format: "text",
                    steps: [
                        {
                            id: 13,
                            description: "01",
                            description_format: "text",
                            expected_results: "01",
                            expected_results_format: "text",
                            rank: 1,
                        },
                        {
                            id: 14,
                            description: "This is text",
                            description_format: "text",
                            expected_results: "text\nwith\nnewlines",
                            expected_results_format: "text",
                            rank: 2,
                        },
                        {
                            id: 15,
                            description: "<p>This is HTML</p>",
                            description_format: "html",
                            expected_results: "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                            expected_results_format: "html",
                            rank: 3,
                        },
                    ],
                },
                steps_results: {
                    "13": {
                        step_id: 13,
                        status: "passed",
                    },
                    "15": {
                        step_id: 15,
                        status: "blocked",
                    },
                },
            };
            mockFetchSuccess(tlpGet, {
                return_json: {
                    testmanagement_execution_response,
                },
            });

            await getTestManagementExecution(artifact_id);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/testmanagement_executions/101");
        });
    });
});
