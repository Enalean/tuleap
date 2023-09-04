/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import {
    getReport,
    getReportContent,
    getQueryResult,
    updateReport,
    getSortedProjectsIAmMemberOf,
    getTrackersOfProject,
    getCSVReport,
} from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as tlp_fetch from "@tuleap/tlp-fetch";

describe("rest-querier", () => {
    describe("getReport() -", () => {
        it("the REST API will be queried and the report returned", async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            const report = {
                trackers: [{ id: 63 }, { id: 100 }],
                expert_query: '@title = "bla"',
            };
            mockFetchSuccess(tlpGet, {
                return_json: report,
            });

            const result = await getReport(16);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/cross_tracker_reports/16");
            expect(result).toEqual(report);
        });
    });

    describe("getReportContent() -", () => {
        it("the artifacts and the total number of artifacts will be returned", async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            const artifacts = [{ id: 100 }, { id: 33 }];
            const total = "91";
            const headers = {
                /** 'X-PAGINATION-SIZE' */
                get: (): string => total,
            };
            mockFetchSuccess(tlpGet, {
                headers,
                return_json: { artifacts },
            });
            const limit = 30;
            const offset = 30;

            const result = await getReportContent(57, limit, offset);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/cross_tracker_reports/57/content", {
                params: { limit, offset },
            });
            expect(result).toEqual({ artifacts, total });
        });
    });

    describe("getQueryResult() -", () => {
        it("the tracker ids and the expert query will be submitted to the REST API, and the artifacts and the total number of artifacts will be returned", async () => {
            const tlpGet = jest.spyOn(tlp_fetch, "get");
            const artifacts = [{ id: 26 }, { id: 89 }];
            const total = "69";
            const headers = {
                /** 'X-PAGINATION-SIZE' */
                get: (): string => total,
            };
            mockFetchSuccess(tlpGet, {
                headers,
                return_json: { artifacts },
            });
            const limit = 30;
            const offset = 30;

            const trackers_id = [16, 80, 6];
            const expert_query = '@title = "stalky"';
            const result = await getQueryResult(72, trackers_id, expert_query, limit, offset);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/cross_tracker_reports/72/content", {
                params: {
                    limit,
                    offset,
                    query: JSON.stringify({ trackers_id, expert_query }),
                },
            });
            expect(result).toEqual({ artifacts, total });
        });

        describe("updateReport() -", () => {
            it("the REST API will be queried and the report returned", async () => {
                const tlpPut = jest.spyOn(tlp_fetch, "put");
                const expert_query = '@title = "dolous"';
                const trackers_id = [8, 3, 67];
                mockFetchSuccess(tlpPut, {
                    return_json: {
                        trackers_id,
                        expert_query,
                    },
                });

                const result = await updateReport(59, trackers_id, expert_query);

                expect(tlpPut).toHaveBeenCalledWith(
                    "/api/v1/cross_tracker_reports/59",
                    expect.any(Object),
                );
                expect(result).toEqual({ trackers_id, expert_query });
            });
        });

        describe("getSortedProjectsIAmMemberOf() -", () => {
            it("the REST API will be queried and the list of projects will be sorted and returned", async () => {
                const tlpRecursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");
                const projects = [
                    { id: 765, label: "physicianless" },
                    { id: 239, label: "spur" },
                    { id: 487, label: "castellano" },
                ];
                tlpRecursiveGet.mockResolvedValue(projects);

                const result = await getSortedProjectsIAmMemberOf();

                expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/projects", {
                    params: {
                        limit: 50,
                        query: JSON.stringify({ is_member_of: true }),
                    },
                });
                expect(result).toEqual([
                    { id: 487, label: "castellano" },
                    { id: 765, label: "physicianless" },
                    { id: 239, label: "spur" },
                ]);
            });
        });

        describe("getTrackersOfProject() -", () => {
            beforeEach(() => {
                jest.clearAllMocks();
            });

            it("the REST API will be queried and the list of trackers returned", async () => {
                const tlpRecursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");
                const trackers = [{ id: 28 }, { id: 50 }];
                tlpRecursiveGet.mockResolvedValue(trackers);

                const result = await getTrackersOfProject(444);

                expect(tlpRecursiveGet).toHaveBeenCalledWith("/api/v1/projects/444/trackers", {
                    params: {
                        limit: 50,
                        representation: "minimal",
                    },
                });
                expect(result).toEqual(trackers);
            });
        });

        describe("getCSVReport() -", () => {
            afterEach(() => {
                jest.clearAllMocks();
            });

            it("When there is only one page then it will return the first request", async () => {
                const tlpGet = jest.spyOn(tlp_fetch, "get");
                const csv = `"id"\r\n65\r\n88\r\n`;

                tlpGet.mockReturnValue(
                    Promise.resolve({
                        headers: {
                            /** 'X-PAGINATION-SIZE' */
                            get: (): string => "2",
                        },
                        text() {
                            return Promise.resolve(csv);
                        },
                    } as unknown as Response),
                );

                const results = await getCSVReport(72);
                expect(tlpGet).toHaveBeenCalledWith("/plugins/crosstracker/csv_export/72", {
                    params: {
                        limit: 50,
                        offset: 0,
                    },
                });
                expect(tlpGet).toHaveBeenCalledTimes(1);

                expect(results).toEqual(csv);
            });

            it("When there are two pages, then it will drop the header line of the second request, concat the two requests and return them", async () => {
                const tlpGet = jest.spyOn(tlp_fetch, "get");
                const csv = `"id"\r\n61\r\n26\r\n`;

                tlpGet.mockReturnValue(
                    Promise.resolve({
                        headers: {
                            /** 'X-PAGINATION-SIZE' */
                            get: (): string => "70",
                        },
                        text() {
                            return Promise.resolve(csv);
                        },
                    } as unknown as Response),
                );

                const results = await getCSVReport(81);
                expect(tlpGet).toHaveBeenCalledWith("/plugins/crosstracker/csv_export/81", {
                    params: {
                        limit: 50,
                        offset: 0,
                    },
                });
                expect(tlpGet).toHaveBeenCalledWith("/plugins/crosstracker/csv_export/81", {
                    params: {
                        limit: 50,
                        offset: 50,
                    },
                });
                expect(tlpGet).toHaveBeenCalledTimes(2);

                expect(results).toBe(`"id"\r\n61\r\n26\r\n61\r\n26\r\n`);
            });
        });
    });
});
