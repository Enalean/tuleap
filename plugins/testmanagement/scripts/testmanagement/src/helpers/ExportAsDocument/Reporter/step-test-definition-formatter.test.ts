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

import type {
    ArtifactFromReport,
    ArtifactResponse,
    TestExecutionResponse,
} from "@tuleap/plugin-docgen-docx";
import {
    buildStepDefinitionEnhancedWithResultsFunction,
    buildStepDefinitionFunction,
} from "./step-test-definition-formatter";
import type { ArtifactReportResponseStepDefinitionFieldValue } from "@tuleap/plugin-docgen-docx";
import type { ArtifactReportResponseUserRepresentation } from "@tuleap/plugin-docgen-docx";

describe("step-test-definition-formatter", () => {
    it("should build a function that enhance the step definition field with steps results", () => {
        const artifact: ArtifactFromReport = {
            id: 359,
        } as ArtifactFromReport;

        const execution: TestExecutionResponse = {
            definition: {
                artifact: {
                    id: 359,
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
        };

        const format_step_def_builder = buildStepDefinitionEnhancedWithResultsFunction(
            artifact,
            execution
        );

        const field_value = {
            field_id: 24,
            type: "ttmstepdef",
            label: "Step Definition",
            value: [
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
        } as ArtifactReportResponseStepDefinitionFieldValue;

        const enhanced_step_def_value = format_step_def_builder("https://example.com", field_value);

        expect(enhanced_step_def_value).toStrictEqual({
            field_name: "Step Definition",
            content_length: "blockttmstepdefenhanced",
            value_type: "string",
            steps: [
                {
                    description: "01",
                    description_format: "plaintext",
                    expected_results: "01",
                    expected_results_format: "plaintext",
                    rank: 1,
                    status: "passed",
                },
                {
                    description: "This is text",
                    description_format: "plaintext",
                    expected_results: "text\nwith\nnewlines",
                    expected_results_format: "plaintext",
                    rank: 2,
                    status: "notrun",
                },
                {
                    description: "<p>This is HTML</p>",
                    description_format: "html",
                    expected_results: "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                    expected_results_format: "html",
                    rank: 3,
                    status: "blocked",
                },
            ],
            status: "blocked",
            result: "<b>it is blocked</b>",
            attachments: [{ filename: "toto.png", html_url: "https://example.com/path/to/file" }],
            linked_bugs: [
                {
                    id: 1001,
                    title: "It does not work",
                    xref: "bug #1001",
                    html_url: "https://example.com/plugins/tracker/?aid=1001",
                },
            ],
            last_execution_date: "2021-11-04T15:30:00+01:00",
            last_execution_user: "Some name",
        });
    });

    it("when status is null, it means that it is notrun", () => {
        const artifact: ArtifactFromReport = {
            id: 359,
        } as ArtifactFromReport;

        const execution: TestExecutionResponse = {
            definition: {
                artifact: {
                    id: 359,
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
            steps_results: {},
            status: null,
            attachments: [],
            linked_bugs: [],
        };

        const format_step_def_builder = buildStepDefinitionEnhancedWithResultsFunction(
            artifact,
            execution
        );

        const field_value = {
            field_id: 24,
            type: "ttmstepdef",
            label: "Step Definition",
            value: [
                {
                    id: 13,
                    description: "01",
                    description_format: "text",
                    expected_results: "01",
                    expected_results_format: "text",
                    rank: 1,
                },
            ],
        } as ArtifactReportResponseStepDefinitionFieldValue;

        const enhanced_step_def_value = format_step_def_builder("https://example.com", field_value);

        expect(enhanced_step_def_value).toStrictEqual({
            field_name: "Step Definition",
            content_length: "blockttmstepdefenhanced",
            value_type: "string",
            steps: [
                {
                    description: "01",
                    description_format: "plaintext",
                    expected_results: "01",
                    expected_results_format: "plaintext",
                    rank: 1,
                    status: "notrun",
                },
            ],
            status: "notrun",
            result: "",
            attachments: [],
            linked_bugs: [],
            last_execution_date: "",
            last_execution_user: "",
        });
    });

    it("should build a function that returns the step definition field without steps results", () => {
        const format_step_def_builder = buildStepDefinitionFunction();

        const field_value = {
            field_id: 24,
            type: "ttmstepdef",
            label: "Step Definition",
            value: [
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
        } as ArtifactReportResponseStepDefinitionFieldValue;

        const enhanced_step_def_value = format_step_def_builder("https://example.com", field_value);

        expect(enhanced_step_def_value).toStrictEqual({
            field_name: "Step Definition",
            content_length: "blockttmstepdef",
            value_type: "string",
            steps: [
                {
                    description: "01",
                    description_format: "plaintext",
                    expected_results: "01",
                    expected_results_format: "plaintext",
                    rank: 1,
                    status: null,
                },
                {
                    description: "This is text",
                    description_format: "plaintext",
                    expected_results: "text\nwith\nnewlines",
                    expected_results_format: "plaintext",
                    rank: 2,
                    status: null,
                },
                {
                    description: "<p>This is HTML</p>",
                    description_format: "html",
                    expected_results: "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                    expected_results_format: "html",
                    rank: 3,
                    status: null,
                },
            ],
            status: null,
            result: "",
            attachments: [],
            linked_bugs: [],
            last_execution_date: "",
            last_execution_user: "",
        });
    });
});
