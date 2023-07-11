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
import type { TraceabilityMatrixElement } from "../type";
import { createTraceabilityMatrix } from "./create-traceability-matrix";
import * as docgen_docx from "@tuleap/plugin-docgen-docx";
import type {
    ArtifactFromReport,
    ArtifactReportResponseUserRepresentation,
    ArtifactResponse,
    TestExecutionResponse,
} from "@tuleap/plugin-docgen-docx/src";
import type { getTestManagementExecution } from "@tuleap/plugin-docgen-docx";

describe("create-traceability-matrix", () => {
    it("creates rows in the matrix when a test execution with all the information is encountered", async () => {
        const get_test_exec = (): Promise<TestExecutionResponse> =>
            Promise.resolve({
                previous_result: {
                    status: "passed",
                    submitted_by: {
                        display_name: "Realname (username)",
                    } as ArtifactReportResponseUserRepresentation,
                    submitted_on: "2021-07-01T00:00:00+02:00",
                },
                definition: {
                    summary: "Some definition summary",
                    all_requirements: [
                        {
                            id: 888,
                            title: "Requirement title",
                        },
                        {
                            id: 999,
                            title: "Another requirement title",
                        },
                    ],
                },
            } as unknown as TestExecutionResponse);

        vi.spyOn(docgen_docx, "getArtifacts").mockResolvedValue(
            new Map<number, ArtifactResponse>([
                [800, { id: 800, title: "Campaign title" } as ArtifactResponse],
            ])
        );

        const matrix = await buildMatrix(
            [
                {
                    id: 10,
                    xref: "bug #10",
                    title: null,
                    values: [
                        {
                            field_id: 1,
                            type: "ttmstepexec",
                            label: "Step exec",
                            value: null,
                        },
                        {
                            field_id: 2,
                            type: "art_link",
                            label: "Art links",
                            links: [
                                {
                                    id: 700,
                                    title: "",
                                    type: "_covered_by",
                                },
                            ],
                            reverse_links: [
                                {
                                    id: 800,
                                    title: "",
                                    type: null,
                                },
                            ],
                        },
                    ],
                    containers: [],
                },
            ],
            get_test_exec
        );

        expect(matrix).toStrictEqual([
            {
                requirement: "Requirement title",
                result: "passed",
                executed_on: "6/30/2021 10:00:00 PM",
                executed_by: "Realname (username)",
                test: {
                    id: 10,
                    title: "Some definition summary",
                },
                campaign: "Campaign title",
            },
            {
                requirement: "Another requirement title",
                result: "passed",
                executed_on: "6/30/2021 10:00:00 PM",
                executed_by: "Realname (username)",
                test: {
                    id: 10,
                    title: "Some definition summary",
                },
                campaign: "Campaign title",
            },
        ]);
    });

    it("creates an empty matrix when artifacts do not have a step exec field", async () => {
        const matrix = await buildMatrix(
            [
                {
                    id: 11,
                    xref: "bug #11",
                    title: null,
                    values: [],
                    containers: [],
                },
            ],
            vi.fn()
        );

        expect(matrix).toStrictEqual([]);
    });

    it("creates an empty matrix when test executions cannot be retrieved", async () => {
        const get_test_exec = (): Promise<TestExecutionResponse> =>
            Promise.reject(new Error("Something bad"));

        const matrix = await buildMatrix(
            [
                {
                    id: 400,
                    xref: "bug #400",
                    title: null,
                    values: [
                        {
                            field_id: 1,
                            type: "ttmstepexec",
                            label: "Step exec",
                            value: null,
                        },
                        {
                            field_id: 2,
                            type: "art_link",
                            label: "Art links",
                            links: [
                                {
                                    id: 7400,
                                    title: "",
                                    type: "_covered_by",
                                },
                            ],
                            reverse_links: [
                                {
                                    id: 8400,
                                    title: "",
                                    type: null,
                                },
                            ],
                        },
                    ],
                    containers: [],
                },
            ],
            get_test_exec
        );

        expect(matrix).toStrictEqual([]);
    });

    it("creates an empty matrix when artifacts do not have an artifact link field", async () => {
        const matrix = await buildMatrix(
            [
                {
                    id: 14,
                    xref: "bug #14",
                    title: null,
                    values: [
                        {
                            field_id: 1,
                            type: "ttmstepexec",
                            label: "Step exec",
                            value: null,
                        },
                    ],
                    containers: [],
                },
            ],
            vi.fn()
        );

        expect(matrix).toStrictEqual([]);
    });

    it("does not add a row to the matrix when the campaign cannot be identified", async () => {
        const get_test_exec = (): Promise<TestExecutionResponse> =>
            Promise.resolve({
                previous_result: {
                    status: "passed",
                    submitted_by: {
                        display_name: "Realname (username)",
                    } as ArtifactReportResponseUserRepresentation,
                    submitted_on: "2021-07-01T00:00:00+02:00",
                },
            } as TestExecutionResponse);

        const matrix = await buildMatrix(
            [
                {
                    id: 13,
                    xref: "bug #13",
                    title: null,
                    values: [
                        {
                            field_id: 1,
                            type: "ttmstepexec",
                            label: "Step exec",
                            value: null,
                        },
                        {
                            field_id: 2,
                            type: "art_link",
                            label: "Art links",
                            links: [
                                {
                                    id: 702,
                                    title: "",
                                    type: "_covered_by",
                                },
                            ],
                            reverse_links: [],
                        },
                    ],
                    containers: [],
                },
            ],
            get_test_exec
        );

        expect(matrix).toStrictEqual([]);
    });

    it("creates an empty matrix when test executions are not linked to a requirement", async () => {
        const get_test_exec = (): Promise<TestExecutionResponse> =>
            Promise.resolve({
                previous_result: {
                    status: "passed",
                    submitted_by: {
                        display_name: "Realname (username)",
                    } as ArtifactReportResponseUserRepresentation,
                    submitted_on: "2021-07-01T00:00:00+02:00",
                },
                definition: {
                    summary: "Some definition summary",
                    all_requirements: [],
                },
            } as unknown as TestExecutionResponse);

        const matrix = await buildMatrix(
            [
                {
                    id: 15,
                    xref: "bug #15",
                    title: null,
                    values: [
                        {
                            field_id: 1,
                            type: "ttmstepexec",
                            label: "Step exec",
                            value: null,
                        },
                        {
                            field_id: 2,
                            type: "art_link",
                            label: "Art links",
                            links: [
                                {
                                    id: 700,
                                    title: "",
                                    type: "_covered_by",
                                },
                            ],
                            reverse_links: [
                                {
                                    id: 800,
                                    title: "",
                                    type: null,
                                },
                            ],
                        },
                    ],
                    containers: [],
                },
            ],
            get_test_exec
        );

        expect(matrix).toStrictEqual([]);
    });

    it("fallbacks on default title with the ID if the campaigns cannot be retrieved for some reasons", async () => {
        const get_test_exec = (): Promise<TestExecutionResponse> =>
            Promise.resolve({
                previous_result: {
                    status: "passed",
                    submitted_by: {
                        display_name: "Realname (username)",
                    } as ArtifactReportResponseUserRepresentation,
                    submitted_on: "2021-07-01T00:00:00+02:00",
                },
                definition: {
                    summary: "Some definition summary",
                    all_requirements: [
                        {
                            id: 888,
                            title: "Requirement title",
                        },
                    ],
                },
            } as unknown as TestExecutionResponse);

        vi.spyOn(docgen_docx, "getArtifacts").mockRejectedValue(new Error());

        const matrix = await buildMatrix(
            [
                {
                    id: 10,
                    xref: "bug #10",
                    title: null,
                    values: [
                        {
                            field_id: 1,
                            type: "ttmstepexec",
                            label: "Step exec",
                            value: null,
                        },
                        {
                            field_id: 2,
                            type: "art_link",
                            label: "Art links",
                            links: [
                                {
                                    id: 700,
                                    title: "",
                                    type: "_covered_by",
                                },
                            ],
                            reverse_links: [
                                {
                                    id: 800,
                                    title: "",
                                    type: null,
                                },
                            ],
                        },
                    ],
                    containers: [],
                },
            ],
            get_test_exec
        );

        expect(matrix).toStrictEqual([
            {
                requirement: "Requirement title",
                result: "passed",
                executed_on: "6/30/2021 10:00:00 PM",
                executed_by: "Realname (username)",
                test: {
                    id: 10,
                    title: "Some definition summary",
                },
                campaign: "#800",
            },
        ]);
    });
});

function buildMatrix(
    artifacts: ReadonlyArray<ArtifactFromReport>,
    get_test_exec: typeof getTestManagementExecution
): Promise<ReadonlyArray<TraceabilityMatrixElement>> {
    return createTraceabilityMatrix(artifacts, { locale: "en-US", timezone: "UTC" }, get_test_exec);
}
