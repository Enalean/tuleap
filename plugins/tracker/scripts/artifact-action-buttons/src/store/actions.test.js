/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { loadProjectList, loadTrackerList, move, moveDryRun } from "./actions.js";
import * as rest_querier from "../api/rest-querier.js";
import * as window_helper from "../window-helper.js";

describe("Store actions", () => {
    let context, redirectTo;
    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {},
        };
        redirectTo = jest.spyOn(window_helper, "redirectTo").mockImplementation(() => {});
    });

    describe("loadProjectList", () => {
        let getProjectList;
        beforeEach(() => {
            getProjectList = jest.spyOn(rest_querier, "getProjectList");
        });

        it("When I want to load the project, Then it should fetch them asynchronously and put them in the store.", async () => {
            const projects = [
                {
                    id: 102,
                    label: "Project name",
                },
            ];

            getProjectList.mockReturnValue(Promise.resolve(projects));

            await loadProjectList(context);

            expect(context.commit).toHaveBeenCalledWith("saveProjects", projects);
            expect(context.commit).toHaveBeenCalledWith("resetProjectLoading");
        });

        it("When the server responds with an error the error message is stored", async () => {
            const error_json = {
                error: {
                    code: "403",
                    message: "error",
                },
            };
            mockFetchError(getProjectList, { error_json });

            await loadProjectList(context);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "error");
        });
    });

    describe("loadTrackerList", () => {
        let getTrackerList;
        beforeEach(() => {
            getTrackerList = jest.spyOn(rest_querier, "getTrackerList");
            context.state = {
                selected_project_id: 101,
            };
        });

        it("When I want to load the tracker, Then it should fetch them asynchronously and put them in the store.", async () => {
            const trackers = [
                {
                    id: 10,
                    label: "Tracker name",
                },
            ];
            const project_id = 106;

            getTrackerList.mockReturnValue(Promise.resolve(trackers));

            await loadTrackerList(context, project_id);

            expect(context.commit).toHaveBeenCalledWith(
                "loadingTrackersAfterProjectSelected",
                project_id
            );
            expect(context.commit).toHaveBeenCalledWith("saveTrackers", trackers);
            expect(context.commit).toHaveBeenCalledWith("resetTrackersLoading");
        });

        it("When the server responds with an error the error message is stored", async () => {
            const error_json = {
                error: {
                    code: "403",
                    message: "error",
                },
            };
            mockFetchError(getTrackerList, { error_json });

            await loadTrackerList(context);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "error");
        });
    });

    describe("move", () => {
        let moveArtifact;
        beforeEach(() => {
            moveArtifact = jest.spyOn(rest_querier, "moveArtifact");
        });

        it("When I want to process the move, Then it should process move.", async () => {
            moveArtifact.mockReturnValue(Promise.resolve());
            const artifact_id = 101;
            const tracker_id = 5;
            context.state.selected_tracker = {
                tracker_id,
            };

            await move(context, artifact_id);
            expect(moveArtifact).toHaveBeenCalledWith(artifact_id, tracker_id);
            expect(redirectTo).toHaveBeenCalledWith("/plugins/tracker/?aid=" + artifact_id);
        });

        it("When the server responds with an error the error message is stored", async () => {
            const error_json = {
                error: {
                    code: "403",
                    message: "error",
                },
            };
            mockFetchError(moveArtifact, { error_json });

            const artifact_id = 101;
            const tracker_id = 5;
            context.state.selected_tracker = {
                tracker_id,
            };

            await move(context, artifact_id);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "error");
            expect(redirectTo).not.toHaveBeenCalled();
        });
    });

    describe("move dry run", () => {
        let moveDryRunArtifact, moveArtifact;
        beforeEach(() => {
            moveDryRunArtifact = jest.spyOn(rest_querier, "moveDryRunArtifact");

            moveArtifact = jest.spyOn(rest_querier, "moveArtifact");
        });

        it("When I process move in Dry run, if at least one field has en error, I store dry run has been processed in store", async () => {
            const fields = {
                fields_not_migrated: ["not_migrated"],
                fields_partially_migrated: [],
                fields_migrated: [],
            };
            const return_json = {
                dry_run: {
                    fields,
                },
            };

            mockFetchSuccess(moveDryRunArtifact, { return_json });

            const artifact_id = 101;
            const tracker_id = 5;
            context.state.selected_tracker = {
                tracker_id,
            };

            await moveDryRun(context, artifact_id);
            expect(context.commit).toHaveBeenCalledWith("switchToProcessingMove");
            expect(context.commit).toHaveBeenCalledWith("hasProcessedDryRun", fields);
            expect(context.commit).toHaveBeenCalledWith("resetProcessingMove");
            expect(redirectTo).not.toHaveBeenCalled();
        });

        it("When I process move in Dry run, if all field can be migrated, I process the move", async () => {
            const return_json = {
                dry_run: {
                    fields: {
                        fields_not_migrated: [],
                        fields_partially_migrated: [],
                        fields_migrated: ["fully_migrated"],
                    },
                },
            };

            mockFetchSuccess(moveDryRunArtifact, { return_json });
            moveArtifact.mockReturnValue(Promise.resolve());

            const artifact_id = 101;
            const tracker_id = 5;
            context.state.selected_tracker = {
                tracker_id,
            };

            await moveDryRun(context, artifact_id);

            expect(context.commit).toHaveBeenCalledWith("switchToProcessingMove");
            expect(moveArtifact).toHaveBeenCalledWith(artifact_id, tracker_id);
            expect(context.commit).toHaveBeenCalledWith("resetProcessingMove");
            expect(redirectTo).toHaveBeenCalledWith("/plugins/tracker/?aid=" + artifact_id);
        });
    });
});
