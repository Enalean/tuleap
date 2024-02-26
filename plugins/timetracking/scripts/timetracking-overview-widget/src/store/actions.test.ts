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
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
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

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(okAsync([]));
            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(okAsync(report));

            await store.initWidgetWithReport();
            expect(store.error_message).toBeNull();
            expect(store.selected_trackers).toStrictEqual(report.trackers);
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

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(okAsync(trackers));

            await store.loadTimes();

            expect(store.trackers_times).toStrictEqual(trackers);
        });
    });

    describe("loadTimes - rest errors", () => {
        it("Given a Fault, Then the store's error message should be set.", async (): Promise<void> => {
            const api_fault = Fault.fromMessage("403 Forbidden");

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(errAsync(api_fault));

            await store.loadTimes();

            expect(store.success_message).toBeNull();
            expect(store.error_message).toBe(String(api_fault));
        });
    });

    describe("GetProjects - success", () => {
        it("Given a success response, When projects are received, Then no message error is received", async (): Promise<void> => {
            const projects = [
                { id: 765, label: "timetracking" } as ProjectReference,
                { id: 239, label: "projectTest" } as ProjectReference,
            ];

            jest.spyOn(rest_querier, "getProjectsWithTimetracking").mockReturnValue(
                okAsync(projects),
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
                okAsync(trackers),
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

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(okAsync([]));
            jest.spyOn(rest_querier, "saveNewReport").mockReturnValue(okAsync(report));

            await store.saveReport(success_message);

            expect(store.selected_trackers).toStrictEqual(report.trackers);
            expect(store.is_report_saved).toBe(true);
            expect(store.success_message).toBe(success_message);
            expect(store.error_message).toBeNull();
        });
    });

    describe("SaveReport - error", () => {
        it("Given a rest error while saving the report, Then it should set the store's error message with the Fault", async (): Promise<void> => {
            const api_fault = Fault.fromMessage("Oops!");

            jest.spyOn(rest_querier, "saveNewReport").mockReturnValue(errAsync(api_fault));

            await store.saveReport("Report has been successfully saved");

            expect(store.success_message).toBeNull();
            expect(store.error_message).toBe(String(api_fault));
        });
    });
});
