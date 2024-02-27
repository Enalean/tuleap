/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { describe, it, expect, jest } from "@jest/globals";
import { okAsync } from "neverthrow";
import { uri } from "@tuleap/fetch-result";
import * as fetch_result from "@tuleap/fetch-result";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type {
    OverviewReport,
    OverviewReportTracker,
} from "@tuleap/plugin-timetracking-rest-api-types";

import {
    getProjectsWithTimetracking,
    getTrackersFromReport,
    getTrackersWithTimetracking,
    saveNewReport,
} from "./rest-querier";

describe("rest-querier", (): void => {
    describe("Get Report() -", (): void => {
        it("the REST API will be queried : report with its trackers is returned", async (): Promise<void> => {
            const report: OverviewReport = {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [{ id: 1, label: "timetracking_tracker" } as OverviewReportTracker],
                invalid_trackers: [],
            };
            const getJSON = jest.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(report));

            const result = await getTrackersFromReport(1);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(getJSON).toHaveBeenCalledWith(uri`/api/v1/timetracking_reports/${report.id}`);
            expect(result.value).toStrictEqual(report);
        });
    });

    describe("Get Report's times() -", (): void => {
        it("the REST API will be queried : trackers withs artefacts and times are returned", async (): Promise<void> => {
            const trackers: OverviewReportTracker[] = [
                {
                    id: 16,
                    label: "tracker",
                    project: {} as ProjectReference,
                    uri: "",
                },
                {
                    id: 18,
                    label: "tracker 2",
                    project: {} as ProjectReference,
                    uri: "",
                },
            ];
            const report_id = 1;
            const getJSON = jest.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(trackers));
            const result = await getTrackersFromReport(report_id);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(getJSON).toHaveBeenCalledWith(uri`/api/v1/timetracking_reports/${report_id}`);
            expect(result.value).toStrictEqual(trackers);
        });
    });

    describe("getProjects() -", (): void => {
        it("the REST route projects will be queried with with_time_tracking parameter and the projects returned", async (): Promise<void> => {
            const project_765 = { id: 765, label: "timetracking" } as ProjectReference;
            const project_239 = { id: 239, label: "projectTest" } as ProjectReference;

            const getJSON = jest
                .spyOn(fetch_result, "getJSON")
                .mockReturnValue(okAsync([project_765, project_239]));
            const result = await getProjectsWithTimetracking();
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(getJSON).toHaveBeenCalledWith(uri`/api/v1/projects`, {
                params: {
                    limit: 50,
                    offset: 0,
                    query: JSON.stringify({ with_time_tracking: true }),
                },
            });
            expect(result.value).toStrictEqual([project_765, project_239]);
        });
    });

    describe("getTrackers() -", (): void => {
        it("the  REST route projects/id/trackers will be queried project id and with_time_tracking parameter and the trackers returned", async (): Promise<void> => {
            const trackers = [
                { id: 16, label: "tracker_1" } as OverviewReportTracker,
                { id: 18, label: "tracker_2" } as OverviewReportTracker,
            ];
            const project_id = 102;
            const getJSON = jest.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(trackers));
            const result = await getTrackersWithTimetracking(project_id);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(getJSON).toHaveBeenCalledWith(uri`/api/v1/projects/${project_id}/trackers`, {
                params: {
                    representation: "minimal",
                    limit: 50,
                    offset: 0,
                    query: JSON.stringify({ with_time_tracking: true }),
                },
            });
            expect(result.value).toStrictEqual(trackers);
        });
    });

    describe("Save new Report() -", (): void => {
        it("the REST API will be queried : report with its new trackers is returned", async (): Promise<void> => {
            const updated_report: OverviewReport = {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [
                    { id: 1, label: "timetracking_tracker" } as OverviewReportTracker,
                    { id: 2, label: "timetracking_tracker_2" } as OverviewReportTracker,
                ],
                invalid_trackers: [],
            };
            const report_id = 1;
            const put = jest
                .spyOn(fetch_result, "putJSON")
                .mockReturnValue(okAsync(updated_report));

            const trackers_id = [1, 2];
            const result = await saveNewReport(report_id, trackers_id);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(put).toHaveBeenCalledWith(uri`/api/v1/timetracking_reports/${report_id}`, {
                trackers_id,
            });

            expect(result.value).toStrictEqual(updated_report);
        });
    });
});
