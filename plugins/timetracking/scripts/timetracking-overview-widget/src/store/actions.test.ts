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

import { describe, beforeEach, afterEach, it, expect, jest } from "@jest/globals";
import { setActivePinia, createPinia } from "pinia";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { ERROR_OCCURRED } from "@tuleap/plugin-timetracking-constants";
import type {
    OverviewReport,
    OverviewReportTracker,
    TrackerWithTimes,
} from "@tuleap/plugin-timetracking-rest-api-types";
import { useOverviewWidgetTestStore } from "../../tests/helpers/pinia-test-store";
import type { OverviewWidgetStoreInstance } from "../../tests/helpers/pinia-test-store";
import * as rest_querier from "../api/rest-querier";

describe("Store actions", () => {
    let store: OverviewWidgetStoreInstance;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useOverviewWidgetTestStore();
    });

    afterEach(() => {
        store.$reset();
    });

    describe("initWidgetWithReport - success", () => {
        it("Given a success response, When report is received, Then no message error is received", async (): Promise<void> => {
            const report: OverviewReport = {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [{ id: 1, label: "timetracking_tracker" } as OverviewReportTracker],
                invalid_trackers: [],
            };

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(Promise.resolve([]));
            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(
                Promise.resolve(report),
            );

            await store.initWidgetWithReport();
            expect(store.error_message).toBeNull();
            expect(store.selected_trackers).toStrictEqual(report.trackers);
        });
    });

    describe("initWidgetWithReport - rest errors", () => {
        it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async (): Promise<void> => {
            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(Promise.reject());

            await store.initWidgetWithReport();
            expect(store.error_message).toBe(ERROR_OCCURRED);
        });
    });

    describe("loadTimes - success", () => {
        it("Given a success response, When times are received, Then no message error is received", async (): Promise<void> => {
            const trackers: TrackerWithTimes[] = [
                {
                    id: 16,
                    label: "tracker",
                    project: {} as ProjectReference,
                    uri: "",
                    time_per_user: [],
                },
                {
                    id: 18,
                    label: "tracker 2",
                    project: {} as ProjectReference,
                    uri: "",
                    time_per_user: [],
                },
            ];

            store.is_loading = true;

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(
                Promise.resolve(trackers),
            );

            await store.loadTimes();

            expect(store.trackers_times).toStrictEqual(trackers);
            expect(store.is_loading).toBe(false);
        });
    });

    describe("loadTimes - rest errors", () => {
        it("Given a rest error with a known error message, When a json error message is received, Then the message is extracted in the component 's error_message private property.", async (): Promise<void> => {
            const response = {
                json(): Promise<Record<string, unknown>> {
                    return Promise.resolve({ error: { code: 403, message: "Forbidden" } });
                },
            } as Response;

            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(
                Promise.reject(new FetchWrapperError("?edskmlsdq", response)),
            );

            await store.initWidgetWithReport();

            expect(store.success_message).toBeNull();
            expect(store.error_message).toBe("403 Forbidden");
        });

        it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's error_message private property.", async (): Promise<void> => {
            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(Promise.reject());

            await store.initWidgetWithReport();

            expect(store.success_message).toBeNull();
            expect(store.error_message).toBe(ERROR_OCCURRED);
        });
    });

    describe("GetProjects - success", () => {
        it("Given a success response, When projects are received, Then no message error is received", async (): Promise<void> => {
            const projects = [
                { id: 765, label: "timetracking" } as ProjectReference,
                { id: 239, label: "projectTest" } as ProjectReference,
            ];

            jest.spyOn(rest_querier, "getProjectsWithTimetracking").mockReturnValue(
                Promise.resolve(projects),
            );

            await store.getProjects();

            expect(store.success_message).toBeNull();
            expect(store.error_message).toBeNull();
            expect(store.projects).toStrictEqual(projects);
        });
    });

    describe("GetTrackers - success", () => {
        it("Given a success response, When trackers are received, Then no message error is received", async (): Promise<void> => {
            const project_id = 102;
            const trackers = [
                {
                    id: 16,
                    label: "tracker_1",
                    project: { id: project_id },
                } as OverviewReportTracker,
                {
                    id: 18,
                    label: "tracker_2",
                    project: { id: project_id },
                } as OverviewReportTracker,
            ];

            jest.spyOn(rest_querier, "getTrackersWithTimetracking").mockReturnValue(
                Promise.resolve(trackers),
            );

            store.selected_trackers = [];

            await store.getTrackers(project_id);

            expect(store.success_message).toBeNull();
            expect(store.error_message).toBeNull();
            expect(store.trackers).toStrictEqual([
                { ...trackers[0], disabled: false },
                { ...trackers[1], disabled: false },
            ]);
        });
    });

    describe("SaveReport - success", () => {
        it("Given a success response, When report is received, Then no message error is received", async (): Promise<void> => {
            const success_message = "Report has been successfully saved";
            const report: OverviewReport = {
                id: 1,
                uri: "timetracking_reports/1",
                trackers: [
                    { id: 1, label: "timetracking_tracker" } as OverviewReportTracker,
                    { id: 2, label: "timetracking_tracker_2" } as OverviewReportTracker,
                ],
                invalid_trackers: [],
            };

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(Promise.resolve([]));
            jest.spyOn(rest_querier, "saveNewReport").mockReturnValue(Promise.resolve(report));

            await store.saveReport(success_message);

            expect(store.selected_trackers).toStrictEqual(report.trackers);
            expect(store.is_report_saved).toBe(true);
            expect(store.success_message).toBe(success_message);
            expect(store.error_message).toBeNull();
        });
    });

    describe("SaveReport - error", () => {
        it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async (): Promise<void> => {
            jest.spyOn(rest_querier, "saveNewReport").mockReturnValue(Promise.reject());

            await store.saveReport("Report has been successfully saved");

            expect(store.success_message).toBeNull();
            expect(store.error_message).toBe(ERROR_OCCURRED);
        });
    });
});
