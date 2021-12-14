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
import * as querier from "./execution-querier";
import type { TestExecutionResponse } from "@tuleap/plugin-docgen-docx";

describe("getTraceabilityMatrix", () => {
    it("should return empty array if no campaign", async () => {
        const matrix = await getTraceabilityMatrix([], {
            locale: "en-US",
            timezone: "UTC",
        });
        expect(matrix).toStrictEqual([]);
    });

    it("should return empty array if no requirement", async () => {
        jest.spyOn(querier, "getExecutions").mockResolvedValue([
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
        ]);

        const matrix = await getTraceabilityMatrix([{ id: 101 } as Campaign], {
            locale: "en-US",
            timezone: "UTC",
        });
        expect(matrix).toStrictEqual([]);
    });

    it("should return the requirements with their test", async () => {
        jest.spyOn(querier, "getExecutions").mockResolvedValue([
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
        ]);

        const matrix = await getTraceabilityMatrix(
            [{ id: 101, label: "Tuleap 13.4" } as Campaign],
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

    it("should return the tests status", async () => {
        jest.spyOn(querier, "getExecutions").mockResolvedValue([
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
        ]);

        const matrix = await getTraceabilityMatrix(
            [{ id: 101, label: "Tuleap 13.4" } as Campaign],
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

    it("should collects requirements accross all campaigns", async () => {
        jest.spyOn(querier, "getExecutions").mockImplementation(
            (campaign: Campaign): Promise<TestExecutionResponse[]> => {
                if (campaign.id === 101) {
                    return Promise.resolve([
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
                    ]);
                }

                if (campaign.id === 102) {
                    return Promise.resolve([
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
                    ]);
                }

                throw Error("Unknown campaign");
            }
        );

        const matrix = await getTraceabilityMatrix(
            [
                { id: 101, label: "Tuleap 13.4" } as Campaign,
                { id: 102, label: "New features" } as Campaign,
            ],
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

    it("should merge requirements if they are covered by different test executions", async () => {
        jest.spyOn(querier, "getExecutions").mockImplementation(
            (campaign: Campaign): Promise<TestExecutionResponse[]> => {
                if (campaign.id === 101) {
                    return Promise.resolve([
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
                    ]);
                }

                if (campaign.id === 102) {
                    return Promise.resolve([
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
                    ]);
                }

                throw Error("Unknown campaign");
            }
        );

        const matrix = await getTraceabilityMatrix(
            [
                { id: 101, label: "Tuleap 13.4" } as Campaign,
                { id: 102, label: "New features" } as Campaign,
            ],
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
