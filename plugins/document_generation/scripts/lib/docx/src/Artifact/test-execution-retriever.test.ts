/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import type {
    ArtifactReportResponseUserRepresentation,
    ArtifactResponse,
    TestExecutionResponse,
} from "../type";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { getTestManagementExecution } from "./test-execution-retriever";

vi.mock("@tuleap/tlp-fetch");

describe("getTestManagementExecution", () => {
    it("Given an artifact id, Then it will get the testmanagement execution", async () => {
        const artifact_id = 101;
        const tlpGet = vi.spyOn(tlp, "get");

        const testmanagement_execution_response: TestExecutionResponse = {
            definition: {
                artifact: {
                    id: 101,
                } as ArtifactResponse,
                id: 101,
                summary: "Summary",
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
                all_requirements: [
                    {
                        id: 888,
                        title: null,
                        xref: "story #888",
                        tracker: {
                            id: 111,
                        },
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
            previous_result: {
                submitted_on: "2021-11-04T15:30:00+01:00",
                submitted_by: {
                    display_name: "Some name",
                } as ArtifactReportResponseUserRepresentation,
                status: "passed",
                result: "",
            },
            status: "blocked",
            attachments: [],
            linked_bugs: [],
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
