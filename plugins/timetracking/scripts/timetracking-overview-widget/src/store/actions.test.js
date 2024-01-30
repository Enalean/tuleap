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

import * as actions from "./actions.js";
import initial_state from "./state.js";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { ERROR_OCCURRED } from "@tuleap/plugin-timetracking-constants";
import * as rest_querier from "../api/rest-querier.js";

describe("Store actions", () => {
    let context;
    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: { ...initial_state },
        };
    });

    describe("initWidgetWithReport - success", () => {
        it("Given a success response, When report is received, Then no message error is received", async () => {
            const report = [
                {
                    id: 1,
                    uri: "timetracking_reports/1",
                    trackers: [{ id: 1, label: "timetracking_tracker" }],
                },
            ];

            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(
                Promise.resolve(report),
            );

            await actions.initWidgetWithReport(context);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setSelectedTrackers", report.trackers);
        });
    });

    describe("initWidgetWithReport - rest errors", () => {
        it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async () => {
            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(Promise.reject());

            await actions.initWidgetWithReport(context);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", ERROR_OCCURRED);
        });
    });

    describe("loadTimes - success", () => {
        it("Given a success response, When times are received, Then no message error is received", async () => {
            let trackers = [
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
            context.state.trackers_times = trackers;

            jest.spyOn(rest_querier, "getTimesFromReport").mockReturnValue(
                Promise.resolve(trackers),
            );

            await actions.loadTimes(context);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
            expect(context.commit).toHaveBeenCalledWith("setTrackersTimes", trackers);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });
    });

    describe("loadTimes - rest errors", () => {
        it("Given a rest error with a known error message, When a json error message is received, Then the message is extracted in the component 's error_message private property.", async () => {
            mockFetchError(jest.spyOn(rest_querier, "getTrackersFromReport"), {
                error_json: {
                    error: {
                        code: 403,
                        message: "Forbidden",
                    },
                },
            });

            await actions.initWidgetWithReport(context);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
        });

        it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's error_message private property.", async () => {
            jest.spyOn(rest_querier, "getTrackersFromReport").mockReturnValue(Promise.reject());

            await actions.initWidgetWithReport(context);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", ERROR_OCCURRED);
        });
    });

    describe("GetProjects - success", () => {
        it("Given a success response, When projects are received, Then no message error is received", async () => {
            const projects = [
                { id: 765, label: "timetracking" },
                { id: 239, label: "projectTest" },
            ];

            jest.spyOn(rest_querier, "getProjectsWithTimetracking").mockReturnValue(
                Promise.resolve(projects),
            );

            await actions.getProjects(context);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setProjects", projects);
        });
    });

    describe("GetTrackers - success", () => {
        it("Given a success response, When trackers are received, Then no message error is received", async () => {
            const trackers = [
                { id: 16, label: "tracker_1" },
                { id: 18, label: "tracker_2" },
            ];

            jest.spyOn(rest_querier, "getTrackersWithTimetracking").mockReturnValue(
                Promise.resolve(trackers),
            );

            await actions.getTrackers(context);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setTrackers", trackers);
        });
    });

    describe("SaveReport - success", () => {
        it("Given a success response, When report is received, Then no message error is received", async () => {
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

            jest.spyOn(rest_querier, "saveNewReport").mockReturnValue(Promise.resolve(report));
            const success_message = "Report has been successfully saved";

            await actions.saveReport(context, success_message);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setTrackersIds");
            expect(context.commit).toHaveBeenCalledWith("setSelectedTrackers", report.trackers);
            expect(context.commit).toHaveBeenCalledWith("setSuccessMessage", success_message);
            expect(context.commit).toHaveBeenCalledWith("setIsReportSave", true);
        });
    });

    describe("SaveReport - error", () => {
        it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async () => {
            jest.spyOn(rest_querier, "saveNewReport").mockReturnValue(Promise.reject());
            const success_message = "Report has been successfully saved";

            await actions.saveReport(context, success_message);
            expect(context.commit).toHaveBeenCalledWith("resetMessages");
            expect(context.commit).toHaveBeenCalledWith("setTrackersIds");
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", ERROR_OCCURRED);
        });
    });
});
