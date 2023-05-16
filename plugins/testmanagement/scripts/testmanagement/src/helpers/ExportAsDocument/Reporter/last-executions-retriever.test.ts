/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { Campaign, ExecutionsForCampaignMap } from "../../../type";
import { getLastExecutionForTest } from "./last-executions-retriever";
import type {
    ArtifactReportResponseUserRepresentation,
    ArtifactResponse,
} from "@tuleap/plugin-docgen-docx";

describe("getLastExecutionForTest", () => {
    it("should return the last found execution for a given test when this tests is used in multiple campaigns", () => {
        const executions_map = buildExecutionMapWithSameTestWithDifferentVersions();
        const last_execution = getLastExecutionForTest(359, executions_map);

        expect(last_execution?.definition.artifact.title).toBe("titlev2");
    });

    it("should return the last found execution for a given test", () => {
        const executions_map = buildExecutionMapWith2Tests();
        const last_execution = getLastExecutionForTest(359, executions_map);

        expect(last_execution?.definition.artifact.title).toBe("titlev1");
    });

    it("should return null for a given test if no execution found", () => {
        const executions_map = buildExecutionMapWith2Tests();
        const last_execution = getLastExecutionForTest(99999, executions_map);

        expect(last_execution).toBeNull();
    });
});

function buildExecutionMapWithSameTestWithDifferentVersions(): ExecutionsForCampaignMap {
    const executions_map: ExecutionsForCampaignMap = new Map();
    executions_map.set(47, {
        campaign: { id: 47 } as Campaign,
        executions: [
            {
                definition: {
                    artifact: {
                        id: 359,
                        title: "titlev1",
                    } as ArtifactResponse,
                    id: 359,
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
                previous_result: null,
                steps_results: {
                    "13": {
                        step_id: 13,
                        status: "passed",
                    },
                    "15": {
                        step_id: 15,
                        status: "passed",
                    },
                },
                status: "passed",
                attachments: [],
                linked_bugs: [],
            },
        ],
    });

    executions_map.set(48, {
        campaign: { id: 48 } as Campaign,
        executions: [
            {
                definition: {
                    artifact: {
                        id: 359,
                        title: "titlev2",
                    } as ArtifactResponse,
                    id: 359,
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
                previous_result: {
                    submitted_on: "2021-11-04T15:30:00+01:00",
                    submitted_by: {
                        display_name: "Some name",
                    } as ArtifactReportResponseUserRepresentation,
                    status: "passed",
                    result: "<b>it is blocked</b>",
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
                status: "blocked",
                attachments: [{ filename: "toto.png", html_url: "/path/to/file" }],
                linked_bugs: [
                    {
                        id: 1001,
                        title: "It does not work",
                        xref: "bug #1001",
                    },
                ],
            },
        ],
    });

    return executions_map;
}

function buildExecutionMapWith2Tests(): ExecutionsForCampaignMap {
    const executions_map: ExecutionsForCampaignMap = new Map();
    executions_map.set(47, {
        campaign: { id: 47 } as Campaign,
        executions: [
            {
                definition: {
                    artifact: {
                        id: 359,
                        title: "titlev1",
                    } as ArtifactResponse,
                    id: 359,
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
                previous_result: null,
                steps_results: {
                    "13": {
                        step_id: 13,
                        status: "passed",
                    },
                    "15": {
                        step_id: 15,
                        status: "passed",
                    },
                },
                status: "passed",
                attachments: [],
                linked_bugs: [],
            },
        ],
    });

    executions_map.set(48, {
        campaign: { id: 48 } as Campaign,
        executions: [
            {
                definition: {
                    artifact: {
                        id: 360,
                        title: "titlev2",
                    } as ArtifactResponse,
                    id: 360,
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
                previous_result: {
                    submitted_on: "2021-11-04T15:30:00+01:00",
                    submitted_by: {
                        display_name: "Some name",
                    } as ArtifactReportResponseUserRepresentation,
                    status: "passed",
                    result: "<b>it is blocked</b>",
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
                status: "blocked",
                attachments: [{ filename: "toto.png", html_url: "/path/to/file" }],
                linked_bugs: [
                    {
                        id: 1001,
                        title: "It does not work",
                        xref: "bug #1001",
                    },
                ],
            },
        ],
    });

    return executions_map;
}
