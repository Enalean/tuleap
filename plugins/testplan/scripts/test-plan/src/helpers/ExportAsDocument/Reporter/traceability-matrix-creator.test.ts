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

import { getTraceabilityMatrix } from "./traceability-matrix-creator";
import type { Campaign } from "../../../type";
import type { TestExecutionResponse } from "@tuleap/plugin-docgen-docx";

describe("getTraceabilityMatrix", () => {
    it("should return empty array if no campaign", () => {
        const matrix = getTraceabilityMatrix(new Map(), {
            locale: "en-US",
            timezone: "UTC",
        });
        expect(matrix).toStrictEqual([]);
    });

    it("should return empty array if no requirement", () => {
        const matrix = getTraceabilityMatrix(
            new Map([
                [
                    101,
                    {
                        campaign: { id: 101 } as Campaign,
                        executions: [
                            {
                                definition: {
                                    id: 123,
                                    requirement: null,
                                },
                            } as TestExecutionResponse,
                            {
                                definition: {
                                    id: 124,
                                    requirement: null,
                                },
                            } as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            }
        );
        expect(matrix).toStrictEqual([]);
    });

    it("should return the requirements with their test", () => {
        const matrix = getTraceabilityMatrix(
            new Map([
                [
                    101,
                    {
                        campaign: { id: 101, label: "Tuleap 13.4" } as Campaign,
                        executions: [
                            {
                                definition: {
                                    id: 123,
                                    summary: "Test A",
                                    requirement: {
                                        id: 1231,
                                        title: "Lorem",
                                    },
                                },
                                previous_result: null,
                            } as TestExecutionResponse,
                            {
                                definition: {
                                    id: 124,
                                    summary: "Test B",
                                    requirement: {
                                        id: 1241,
                                        title: "Ipsum",
                                    },
                                },
                                previous_result: null,
                            } as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            }
        );
        expect(matrix).toStrictEqual([
            {
                requirement: {
                    id: 1231,
                    title: "Lorem",
                },
                tests: [
                    {
                        id: 123,
                        title: "Test A",
                        campaign: "Tuleap 13.4",
                        executed_by: null,
                        executed_on: null,
                        status: null,
                    },
                ],
            },
            {
                requirement: {
                    id: 1241,
                    title: "Ipsum",
                },
                tests: [
                    {
                        id: 124,
                        title: "Test B",
                        campaign: "Tuleap 13.4",
                        executed_by: null,
                        executed_on: null,
                        status: null,
                    },
                ],
            },
        ]);
    });

    it("should return the tests status", () => {
        const matrix = getTraceabilityMatrix(
            new Map([
                [
                    101,
                    {
                        campaign: { id: 101, label: "Tuleap 13.4" } as Campaign,
                        executions: [
                            {
                                definition: {
                                    id: 123,
                                    summary: "Test A",
                                    requirement: {
                                        id: 1231,
                                        title: "Lorem",
                                    },
                                },
                                previous_result: {
                                    status: "passed",
                                    submitted_on: "2020-06-23T08:01:04-04:00",
                                    submitted_by: {
                                        display_name: "John Doe",
                                    },
                                },
                            } as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            }
        );
        expect(matrix).toStrictEqual([
            {
                requirement: {
                    id: 1231,
                    title: "Lorem",
                },
                tests: [
                    {
                        id: 123,
                        title: "Test A",
                        campaign: "Tuleap 13.4",
                        executed_by: "John Doe",
                        executed_on: "6/23/2020 12:01:04 PM",
                        status: "passed",
                    },
                ],
            },
        ]);
    });

    it("should collects requirements across all campaigns", () => {
        const matrix = getTraceabilityMatrix(
            new Map([
                [
                    101,
                    {
                        campaign: { id: 101, label: "Tuleap 13.4" } as Campaign,
                        executions: [
                            {
                                definition: {
                                    id: 123,
                                    summary: "Test A",
                                    requirement: {
                                        id: 1231,
                                        title: "Lorem",
                                    },
                                },
                                previous_result: null,
                            } as TestExecutionResponse,
                        ],
                    },
                ],
                [
                    102,
                    {
                        campaign: { id: 102, label: "New features" } as Campaign,
                        executions: [
                            {
                                definition: {
                                    id: 124,
                                    summary: "Test B",
                                    requirement: {
                                        id: 1241,
                                        title: "Ipsum",
                                    },
                                },
                                previous_result: null,
                            } as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            }
        );
        expect(matrix).toStrictEqual([
            {
                requirement: {
                    id: 1231,
                    title: "Lorem",
                },
                tests: [
                    {
                        id: 123,
                        title: "Test A",
                        campaign: "Tuleap 13.4",
                        executed_by: null,
                        executed_on: null,
                        status: null,
                    },
                ],
            },
            {
                requirement: {
                    id: 1241,
                    title: "Ipsum",
                },
                tests: [
                    {
                        id: 124,
                        title: "Test B",
                        campaign: "New features",
                        executed_by: null,
                        executed_on: null,
                        status: null,
                    },
                ],
            },
        ]);
    });

    it("should merge requirements if they are covered by different test executions", () => {
        const matrix = getTraceabilityMatrix(
            new Map([
                [
                    101,
                    {
                        campaign: { id: 101, label: "Tuleap 13.4" } as Campaign,
                        executions: [
                            {
                                definition: {
                                    id: 123,
                                    summary: "Test A",
                                    requirement: {
                                        id: 1231,
                                        title: "Lorem",
                                    },
                                },
                                previous_result: {
                                    status: "passed",
                                    submitted_on: "2020-06-23T08:01:04-04:00",
                                    submitted_by: {
                                        display_name: "John Doe",
                                    },
                                },
                            } as TestExecutionResponse,
                        ],
                    },
                ],
                [
                    102,
                    {
                        campaign: { id: 102, label: "New features" } as Campaign,
                        executions: [
                            {
                                definition: {
                                    id: 124,
                                    summary: "Test B",
                                    requirement: {
                                        id: 1231,
                                        title: "Lorem",
                                    },
                                },
                                previous_result: null,
                            } as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            }
        );
        expect(matrix).toStrictEqual([
            {
                requirement: {
                    id: 1231,
                    title: "Lorem",
                },
                tests: [
                    {
                        id: 123,
                        title: "Test A",
                        campaign: "Tuleap 13.4",
                        executed_by: "John Doe",
                        executed_on: "6/23/2020 12:01:04 PM",
                        status: "passed",
                    },
                    {
                        id: 124,
                        title: "Test B",
                        campaign: "New features",
                        executed_by: null,
                        executed_on: null,
                        status: null,
                    },
                ],
            },
        ]);
    });
});
