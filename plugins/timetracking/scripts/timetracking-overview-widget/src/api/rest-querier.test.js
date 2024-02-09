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
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

import {
    getProjectsWithTimetracking,
    getTrackersFromReport,
    getTrackersWithTimetracking,
    saveNewReport,
} from "./rest-querier.js";

describe("Get Report() -", () => {
    it("the REST API will be queried : report with its trackers is returned", async () => {
        const report = [
            {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [{ id: 1, label: "timetracking_tracker" }],
            },
        ];
        const tlpGet = jest.spyOn(tlp_fetch, "get");
        mockFetchSuccess(tlpGet, {
            return_json: report,
        });

        const result = await getTrackersFromReport(1);

        expect(tlpGet).toHaveBeenCalledWith("/api/v1/timetracking_reports/1");
        expect(result).toEqual([
            {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [{ id: 1, label: "timetracking_tracker" }],
            },
        ]);
    });
});

describe("Get Report's times() -", () => {
    it("the REST API will be queried : trackers withs artefacts and times are returned", async () => {
        const trackers = [
            {
                artifacts: [
                    {
                        minutes: 20,
                    },
                    {
                        minutes: 40,
                    },
                ],
                id: "16",
                label: "tracker",
                project: {},
                uri: "",
            },
            {
                artifacts: [
                    {
                        minutes: 20,
                    },
                ],
                id: "18",
                label: "tracker 2",
                project: {},
                uri: "",
            },
        ];
        const tlpGet = jest.spyOn(tlp_fetch, "get");
        mockFetchSuccess(tlpGet, {
            return_json: trackers,
        });

        const result = await getTrackersFromReport(1);

        expect(tlpGet).toHaveBeenCalledWith("/api/v1/timetracking_reports/1");
        expect(result).toEqual(trackers);
    });
});

describe("getProjects() -", () => {
    it("the REST route projects will be queried with with_time_tracking parameter and the projects returned", async () => {
        const projects = [
            { id: 765, label: "timetracking" },
            { id: 239, label: "projectTest" },
        ];
        const tlpGet = jest.spyOn(tlp_fetch, "get");
        mockFetchSuccess(tlpGet, {
            return_json: projects,
        });

        const result = await getProjectsWithTimetracking();

        expect(tlpGet).toHaveBeenCalledWith("/api/v1/projects", {
            params: {
                limit: 50,
                offset: 0,
                query: JSON.stringify({ with_time_tracking: true }),
            },
        });
        expect(result).toEqual([
            { id: 765, label: "timetracking" },
            { id: 239, label: "projectTest" },
        ]);
    });
});

describe("getTrackers() -", () => {
    it("the  REST route projects/id/trackers will be queried project id and with_time_tracking parameter and the trackers returned", async () => {
        const trackers = [
            { id: 16, label: "tracker_1" },
            { id: 18, label: "tracker_2" },
        ];
        const tlpGet = jest.spyOn(tlp_fetch, "get");
        mockFetchSuccess(tlpGet, {
            return_json: trackers,
        });

        const result = await getTrackersWithTimetracking(102);

        expect(tlpGet).toHaveBeenCalledWith("/api/v1/projects/102/trackers", {
            params: {
                representation: "minimal",
                limit: 50,
                offset: 0,
                query: JSON.stringify({ with_time_tracking: true }),
            },
        });
        expect(result).toEqual([
            { id: 16, label: "tracker_1" },
            { id: 18, label: "tracker_2" },
        ]);
    });
});

describe("Save new Report() -", () => {
    it("the REST API will be queried : report with its new trackers is returned", async () => {
        const report = [
            {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "timetracking_tracker_2" },
                ],
            },
        ];

        const tlpPut = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut, {
            return_json: report,
        });
        const headers = {
            "content-type": "application/json",
        };
        const body = JSON.stringify({
            trackers_id: [1, 2],
        });

        const result = await saveNewReport(1, [1, 2]);

        expect(tlpPut).toHaveBeenCalledWith("/api/v1/timetracking_reports/1", {
            headers,
            body,
        });
        expect(result).toEqual([
            {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "timetracking_tracker_2" },
                ],
            },
        ]);
    });
});
