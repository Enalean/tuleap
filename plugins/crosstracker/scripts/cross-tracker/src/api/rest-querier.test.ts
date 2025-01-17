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

import { describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import type { TrackerReference } from "./cross-tracker-rest-api-types";
import {
    getCSVReport,
    getQueryResult,
    getReport,
    getReportContent,
    getSortedProjectsIAmMemberOf,
    getTrackersOfProject,
    updateReport,
} from "./rest-querier";
import { ProjectIdentifier } from "../domain/ProjectIdentifier";
import type { ProjectInfo } from "../type";

describe("rest-querier", () => {
    describe("getReport()", () => {
        it(`will query the REST API and return the report`, async () => {
            const first_tracker: TrackerReference = {
                id: 63,
                label: "Copeognatha",
                project: { id: 111, label: "runagate", uri: "/projects/111", icon: "🌷" },
            };
            const second_tracker: TrackerReference = {
                id: 54,
                label: "isocymene",
                project: { id: 182, label: "antilens", uri: "/projects/182", icon: "" },
            };
            const report = {
                trackers: [first_tracker, second_tracker],
                expert_query: '@title = "bla"',
                invalid_trackers: [second_tracker],
            };
            const getJSON = vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(report));
            const report_id = 16;

            const result = await getReport(report_id);

            expect(getJSON).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}`,
            );
            if (!result.isOk()) {
                throw Error("Expected an ok");
            }
            expect(result.value.trackers).toStrictEqual([
                {
                    tracker: { id: first_tracker.id, label: first_tracker.label },
                    project: first_tracker.project,
                },
                {
                    tracker: { id: second_tracker.id, label: second_tracker.label },
                    project: second_tracker.project,
                },
            ]);
            expect(result.value.expert_query).toBe(report.expert_query);
            expect(result.value.invalid_trackers).toStrictEqual([second_tracker]);
        });
    });

    describe("getReportContent()", () => {
        it(`will return the artifacts and the total number of artifacts`, async () => {
            const total = 91;
            const collection = { artifacts: [{ id: 100 }, { id: 33 }] };
            const getResponse = vi.spyOn(fetch_result, "getResponse").mockReturnValue(
                okAsync({
                    headers: new Headers({
                        "X-PAGINATION-SIZE": String(total),
                    }),
                    json: () => Promise.resolve(collection),
                } as Response),
            );
            const limit = 30;
            const offset = 30;
            const report_id = 57;

            const result = await getReportContent(report_id, limit, offset);

            expect(getResponse).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}/content`,
                { params: { limit, offset } },
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toStrictEqual({ artifacts: collection.artifacts, total });
        });
    });

    describe("getQueryResult()", () => {
        it(`will send the given tracker ids and expert query to the REST API,
            and will return the artifacts and the total number of artifacts`, async () => {
            const total = 69;
            const collection = { artifacts: [{ id: 26 }, { id: 89 }] };
            const getResponse = vi.spyOn(fetch_result, "getResponse").mockReturnValue(
                okAsync({
                    headers: new Headers({
                        "X-PAGINATION-SIZE": String(total),
                    }),
                    json: () => Promise.resolve(collection),
                } as Response),
            );
            const limit = 30;
            const offset = 30;
            const report_id = 72;
            const trackers_id = [16, 80, 6];
            const expert_query = '@title = "stalky"';

            const result = await getQueryResult(
                report_id,
                trackers_id,
                expert_query,
                limit,
                offset,
            );

            expect(getResponse).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}/content`,
                {
                    params: {
                        limit,
                        offset,
                        query: JSON.stringify({ trackers_id, expert_query }),
                    },
                },
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toStrictEqual({ artifacts: collection.artifacts, total });
        });

        describe("updateReport()", () => {
            it(`will send the given tracker ids and expert query to be saved by the REST API
                and will return the report from the response`, async () => {
                const expert_query = '@title = "dolous"';
                const first_tracker: TrackerReference = {
                    id: 461,
                    label: "deputize",
                    project: { id: 550, label: "uranographist", uri: "/projects/550", icon: "🌷" },
                };
                const second_tracker: TrackerReference = {
                    id: 184,
                    label: "Wiros",
                    project: { id: 616, label: "misperform", uri: "/projects/616", icon: "" },
                };
                const report = {
                    trackers: [first_tracker, second_tracker],
                    expert_query,
                    invalid_trackers: [second_tracker],
                };
                const putJSON = vi.spyOn(fetch_result, "putJSON").mockReturnValue(okAsync(report));
                const report_id = 59;

                const result = await updateReport(
                    report_id,
                    [first_tracker.id, second_tracker.id],
                    expert_query,
                );

                expect(putJSON).toHaveBeenCalledWith(
                    fetch_result.uri`/api/v1/cross_tracker_reports/${report_id}`,
                    expect.any(Object),
                );
                if (!result.isOk()) {
                    throw Error("Expected an Ok");
                }
                expect(result.value.trackers).toStrictEqual([
                    {
                        tracker: { id: first_tracker.id, label: first_tracker.label },
                        project: first_tracker.project,
                    },
                    {
                        tracker: { id: second_tracker.id, label: second_tracker.label },
                        project: second_tracker.project,
                    },
                ]);
                expect(result.value.expert_query).toBe(report.expert_query);
                expect(result.value.invalid_trackers).toStrictEqual([second_tracker]);
            });
        });

        describe("getSortedProjectsIAmMemberOf()", () => {
            it(`will return the list of projects that current user is member of
                and will sort the list by project label`, async () => {
                const projects = [
                    { id: 765, label: "physicianless" },
                    { id: 239, label: "spur" },
                    { id: 487, label: "castellano" },
                ];
                const getAllJSON = vi
                    .spyOn(fetch_result, "getAllJSON")
                    .mockReturnValue(okAsync(projects));

                const result = await getSortedProjectsIAmMemberOf();

                if (!result.isOk()) {
                    throw Error("Expected an Ok");
                }
                expect(getAllJSON).toHaveBeenCalledWith(fetch_result.uri`/api/v1/projects`, {
                    params: {
                        limit: 50,
                        query: JSON.stringify({ is_member_of: true }),
                    },
                });
                expect(result.value).toStrictEqual([
                    { id: 487, label: "castellano" },
                    { id: 765, label: "physicianless" },
                    { id: 239, label: "spur" },
                ]);
            });
        });

        describe("getTrackersOfProject()", () => {
            it(`will return the list of trackers of a given project`, async () => {
                const trackers = [{ id: 28 }, { id: 50 }];
                const getAllJSON = vi
                    .spyOn(fetch_result, "getAllJSON")
                    .mockReturnValue(okAsync(trackers));
                const project_id = 444;

                const result = await getTrackersOfProject(
                    ProjectIdentifier.fromProjectInfo({ id: project_id } as ProjectInfo),
                );

                if (!result.isOk()) {
                    throw Error("Expected an Ok");
                }
                expect(getAllJSON).toHaveBeenCalledWith(
                    fetch_result.uri`/api/v1/projects/${project_id}/trackers`,
                    {
                        params: {
                            limit: 50,
                            representation: "minimal",
                        },
                    },
                );
                expect(result.value).toStrictEqual(trackers);
            });
        });

        describe("getCSVReport()", () => {
            it("When there is only one page then it will return the first request", async () => {
                const csv = `"id"\r\n65\r\n88\r\n`;
                const getTextResponse = vi.spyOn(fetch_result, "getTextResponse").mockReturnValue(
                    okAsync({
                        headers: new Headers({ "X-PAGINATION-SIZE": "2" }),
                        text: () => Promise.resolve(csv),
                    } as Response),
                );
                const report_id = 72;

                const result = await getCSVReport(report_id);

                if (!result.isOk()) {
                    throw Error("Expected an Ok");
                }
                expect(getTextResponse).toHaveBeenCalledWith(
                    fetch_result.uri`/plugins/crosstracker/csv_export/${report_id}`,
                    { params: { limit: 50, offset: 0 } },
                );
                expect(getTextResponse).toHaveBeenCalledTimes(1);
                expect(result.value).toBe(csv);
            });

            it(`When there are two pages, then it will drop the header line of the second request
                concat the two requests and return them`, async () => {
                const csv = `"id"\r\n61\r\n26\r\n`;
                const getTextResponse = vi.spyOn(fetch_result, "getTextResponse").mockReturnValue(
                    okAsync({
                        headers: new Headers({ "X-PAGINATION-SIZE": "70" }),
                        text: () => Promise.resolve(csv),
                    } as Response),
                );
                const report_id = 81;

                const result = await getCSVReport(report_id);

                if (!result.isOk()) {
                    throw Error("Expected an Ok");
                }
                expect(getTextResponse).toHaveBeenCalledWith(
                    fetch_result.uri`/plugins/crosstracker/csv_export/${report_id}`,
                    { params: { limit: 50, offset: 0 } },
                );
                expect(getTextResponse).toHaveBeenCalledWith(
                    fetch_result.uri`/plugins/crosstracker/csv_export/${report_id}`,
                    { params: { limit: 50, offset: 50 } },
                );
                expect(getTextResponse).toHaveBeenCalledTimes(2);

                expect(result.value).toBe(`"id"\r\n61\r\n26\r\n61\r\n26\r\n`);
            });
        });
    });
});
