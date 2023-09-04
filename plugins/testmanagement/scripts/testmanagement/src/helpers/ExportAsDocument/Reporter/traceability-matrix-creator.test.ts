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
                                    all_requirements: [],
                                },
                            } as unknown as TestExecutionResponse,
                            {
                                definition: {
                                    id: 124,
                                    all_requirements: [],
                                },
                            } as unknown as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            },
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
                                    all_requirements: [
                                        {
                                            id: 1231,
                                            title: "Lorem",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                    ],
                                },
                                previous_result: null,
                            } as unknown as TestExecutionResponse,
                            {
                                definition: {
                                    id: 124,
                                    summary: "Test B",
                                    all_requirements: [
                                        {
                                            id: 1241,
                                            title: "Ipsum",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                        {
                                            id: 1251,
                                            title: "Doloret",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                    ],
                                },
                                previous_result: null,
                            } as unknown as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            },
        );

        expect(matrix).toHaveLength(3);
        expect(matrix[0].requirement).toStrictEqual({
            id: 1231,
            title: "Lorem",
            tracker_id: 111,
        });
        expect(matrix[0].tests.size).toBe(1);
        expect(matrix[0].tests.get(123)).toStrictEqual({
            id: 123,
            title: "Test A",
            campaign: "Tuleap 13.4",
            executed_by: null,
            executed_on: null,
            executed_on_date: null,
            status: null,
        });

        expect(matrix[1].requirement).toStrictEqual({
            id: 1241,
            title: "Ipsum",
            tracker_id: 111,
        });
        expect(matrix[1].tests.size).toBe(1);
        expect(matrix[1].tests.get(124)).toStrictEqual({
            id: 124,
            title: "Test B",
            campaign: "Tuleap 13.4",
            executed_by: null,
            executed_on: null,
            executed_on_date: null,
            status: null,
        });

        expect(matrix[2].requirement).toStrictEqual({
            id: 1251,
            title: "Doloret",
            tracker_id: 111,
        });
        expect(matrix[2].tests.size).toBe(1);
        expect(matrix[2].tests.get(124)).toStrictEqual({
            id: 124,
            title: "Test B",
            campaign: "Tuleap 13.4",
            executed_by: null,
            executed_on: null,
            executed_on_date: null,
            status: null,
        });
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
                                    all_requirements: [
                                        {
                                            id: 1231,
                                            title: "Lorem",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                    ],
                                },
                                previous_result: {
                                    status: "passed",
                                    submitted_on: "2020-06-23T08:01:04-04:00",
                                    submitted_by: {
                                        display_name: "John Doe",
                                    },
                                },
                            } as unknown as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            },
        );

        expect(matrix).toHaveLength(1);
        expect(matrix[0].requirement).toStrictEqual({
            id: 1231,
            title: "Lorem",
            tracker_id: 111,
        });
        expect(matrix[0].tests.size).toBe(1);
        expect(matrix[0].tests.get(123)).toStrictEqual({
            id: 123,
            title: "Test A",
            campaign: "Tuleap 13.4",
            executed_by: "John Doe",
            executed_on: "6/23/2020 12:01:04 PM",
            executed_on_date: new Date("2020-06-23T08:01:04-04:00"),
            status: "passed",
        });
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
                                    all_requirements: [
                                        {
                                            id: 1231,
                                            title: "Lorem",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                    ],
                                },
                                previous_result: null,
                            } as unknown as TestExecutionResponse,
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
                                    all_requirements: [
                                        {
                                            id: 1241,
                                            title: "Ipsum",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                    ],
                                },
                                previous_result: null,
                            } as unknown as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            },
        );

        expect(matrix).toHaveLength(2);
        expect(matrix[0].requirement).toStrictEqual({
            id: 1231,
            title: "Lorem",
            tracker_id: 111,
        });
        expect(matrix[0].tests.size).toBe(1);
        expect(matrix[0].tests.get(123)).toStrictEqual({
            id: 123,
            title: "Test A",
            campaign: "Tuleap 13.4",
            executed_by: null,
            executed_on: null,
            executed_on_date: null,
            status: null,
        });

        expect(matrix[1].requirement).toStrictEqual({
            id: 1241,
            title: "Ipsum",
            tracker_id: 111,
        });
        expect(matrix[1].tests.size).toBe(1);
        expect(matrix[1].tests.get(124)).toStrictEqual({
            id: 124,
            title: "Test B",
            campaign: "New features",
            executed_by: null,
            executed_on: null,
            executed_on_date: null,
            status: null,
        });
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
                                    all_requirements: [
                                        {
                                            id: 1231,
                                            title: "Lorem",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                    ],
                                },
                                previous_result: {
                                    status: "passed",
                                    submitted_on: "2020-06-23T08:01:04-04:00",
                                    submitted_by: {
                                        display_name: "John Doe",
                                    },
                                },
                            } as unknown as TestExecutionResponse,
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
                                    all_requirements: [
                                        {
                                            id: 1231,
                                            title: "Lorem",
                                            tracker: {
                                                id: 111,
                                            },
                                        },
                                    ],
                                },
                                previous_result: null,
                            } as unknown as TestExecutionResponse,
                        ],
                    },
                ],
            ]),
            {
                locale: "en-US",
                timezone: "UTC",
            },
        );

        expect(matrix).toHaveLength(1);
        expect(matrix[0].requirement).toStrictEqual({
            id: 1231,
            title: "Lorem",
            tracker_id: 111,
        });
        expect(matrix[0].tests.size).toBe(2);
        expect(matrix[0].tests.get(123)).toStrictEqual({
            id: 123,
            title: "Test A",
            campaign: "Tuleap 13.4",
            executed_by: "John Doe",
            executed_on: "6/23/2020 12:01:04 PM",
            executed_on_date: new Date("2020-06-23T08:01:04-04:00"),
            status: "passed",
        });
        expect(matrix[0].tests.get(124)).toStrictEqual({
            id: 124,
            title: "Test B",
            campaign: "New features",
            executed_by: null,
            executed_on: null,
            executed_on_date: null,
            status: null,
        });
    });

    describe("when same test is exetuced in different campaigns", () => {
        it.each([
            [null, null, null, null],
            [null, "08:01", "failed", "08:01"],
            ["08:01", null, "passed", "08:01"],
            ["08:01", "08:01", "passed", "08:01"],
            ["08:00", "08:01", "failed", "08:01"],
            ["08:01", "08:00", "passed", "08:01"],
        ])(
            `when time of passed test is %s and time of failed test is %s, then test is %s`,
            (
                time_of_passed_test: string | null,
                time_of_failed_test: string | null,
                expected_status: string | null,
                expected_submitted_on_time: string | null,
            ): void => {
                const date_of_passed_test = time_of_passed_test
                    ? `2020-06-23T${time_of_passed_test}:04-04:00`
                    : null;
                const date_of_failed_test = time_of_failed_test
                    ? `2020-06-23T${time_of_failed_test}:04-04:00`
                    : null;
                const expected_submitted_on_date = expected_submitted_on_time
                    ? `2020-06-23T${expected_submitted_on_time}:04-04:00`
                    : null;
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
                                            all_requirements: [
                                                {
                                                    id: 1231,
                                                    title: "Lorem",
                                                    tracker: {
                                                        id: 111,
                                                    },
                                                },
                                            ],
                                        },
                                        previous_result: date_of_passed_test
                                            ? {
                                                  status: "passed",
                                                  submitted_on: date_of_passed_test,
                                                  submitted_by: {
                                                      display_name: "John Doe",
                                                  },
                                              }
                                            : null,
                                    } as unknown as TestExecutionResponse,
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
                                            id: 123,
                                            summary: "Test A",
                                            all_requirements: [
                                                {
                                                    id: 1231,
                                                    title: "Lorem",
                                                    tracker: {
                                                        id: 111,
                                                    },
                                                },
                                            ],
                                        },
                                        previous_result: date_of_failed_test
                                            ? {
                                                  status: "failed",
                                                  submitted_on: date_of_failed_test,
                                                  submitted_by: {
                                                      display_name: "John Doe",
                                                  },
                                              }
                                            : null,
                                    } as unknown as TestExecutionResponse,
                                ],
                            },
                        ],
                    ]),
                    {
                        locale: "en-US",
                        timezone: "UTC",
                    },
                );

                expect(matrix).toHaveLength(1);
                expect(matrix[0].requirement).toStrictEqual({
                    id: 1231,
                    title: "Lorem",
                    tracker_id: 111,
                });
                expect(matrix[0].tests.size).toBe(1);
                expect(matrix[0].tests.get(123)).toStrictEqual({
                    id: 123,
                    title: "Test A",
                    campaign: expected_status === "failed" ? "New features" : "Tuleap 13.4",
                    executed_by: expected_status ? "John Doe" : null,
                    executed_on: expected_submitted_on_date ? "6/23/2020 12:01:04 PM" : null,
                    executed_on_date: expected_submitted_on_date
                        ? new Date(expected_submitted_on_date)
                        : null,
                    status: expected_status,
                });
            },
        );
    });
});
