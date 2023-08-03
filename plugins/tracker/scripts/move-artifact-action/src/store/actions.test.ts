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
import { loadProjectList, loadTrackerList, move, moveDryRun } from "./actions";
import * as rest_querier from "../api/rest-querier";
import * as window_helper from "../window-helper";

import type { Context, RootState, Project, Tracker, DryRunState } from "./types";

const artifact_id = 101,
    tracker_id = 5,
    project_id = 106;

describe("Store actions", () => {
    let context: Context, redirectTo: jest.SpyInstance;
    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {} as RootState,
        } as unknown as Context;
        redirectTo = jest.spyOn(window_helper, "redirectTo").mockImplementation((): void => {
            // Do nothing
        });
    });

    describe("loadProjectList", () => {
        let getProjectList: jest.SpyInstance;

        beforeEach(() => {
            getProjectList = jest.spyOn(rest_querier, "getProjectList");
        });

        it("When I want to load the project, Then it should fetch them asynchronously and put them in the store.", async () => {
            const projects: Project[] = [
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
        let getTrackerList: jest.SpyInstance;
        beforeEach(() => {
            getTrackerList = jest.spyOn(rest_querier, "getTrackerList");
            context.state = {
                selected_project_id: 101,
            } as RootState;
        });

        it("When I want to load the tracker, Then it should fetch them asynchronously and put them in the store.", async () => {
            const trackers: Tracker[] = [
                {
                    id: 10,
                    label: "Tracker name",
                    disabled: false,
                },
            ];

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
            mockFetchError(getTrackerList, {
                error_json: {
                    error: {
                        code: "403",
                        message: "error",
                    },
                },
            });

            await loadTrackerList(context, 10);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "error");
        });
    });

    describe("move", () => {
        let moveArtifact: jest.SpyInstance;
        beforeEach(() => {
            moveArtifact = jest.spyOn(rest_querier, "moveArtifact");
        });

        it("When I want to process the move, Then it should process move.", async () => {
            moveArtifact.mockReturnValue(Promise.resolve());
            context.state.selected_tracker_id = tracker_id;

            await move(context, artifact_id);
            expect(moveArtifact).toHaveBeenCalledWith(artifact_id, tracker_id);
            expect(redirectTo).toHaveBeenCalledWith("/plugins/tracker/?aid=" + artifact_id);
        });

        it("When the server responds with an error the error message is stored", async () => {
            mockFetchError(moveArtifact, {
                error_json: {
                    error: {
                        code: "403",
                        message: "error",
                    },
                },
            });

            context.state.selected_tracker_id = tracker_id;

            await move(context, artifact_id);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "error");
            expect(redirectTo).not.toHaveBeenCalled();
        });
    });

    describe("move dry run", () => {
        let moveDryRunArtifact: jest.SpyInstance, moveArtifact: jest.SpyInstance;

        beforeEach(() => {
            moveDryRunArtifact = jest.spyOn(rest_querier, "moveDryRunArtifact");

            moveArtifact = jest.spyOn(rest_querier, "moveArtifact");
        });

        it("When I process move in Dry run, if at least one field has en error, I store dry run has been processed in store", async () => {
            const fields: DryRunState = {
                fields_not_migrated: [
                    {
                        field_id: 10,
                        label: "Not migrated",
                        name: "not_migrated",
                    },
                ],
                fields_partially_migrated: [
                    {
                        field_id: 11,
                        label: "Partially migrated",
                        name: "partially_migrated",
                    },
                ],
                fields_migrated: [
                    {
                        field_id: 12,
                        label: "Fully migrated",
                        name: "fully_migrated",
                    },
                ],
            };

            mockFetchSuccess(moveDryRunArtifact, {
                return_json: {
                    dry_run: {
                        fields,
                    },
                },
            });

            context.state.selected_tracker_id = tracker_id;

            await moveDryRun(context, artifact_id);
            expect(context.commit).toHaveBeenCalledWith("switchToProcessingMove");
            expect(context.commit).toHaveBeenCalledWith("hasProcessedDryRun", fields);
            expect(context.commit).toHaveBeenCalledWith("resetProcessingMove");
            expect(redirectTo).not.toHaveBeenCalled();
        });

        it("Given that there are no fields can be moved or partially moved, then the move should be blocked", async () => {
            const fields = {
                fields_not_migrated: [
                    {
                        field_id: 10,
                        label: "Not migrated",
                        name: "not_migrated",
                    },
                ],
                fields_partially_migrated: [],
                fields_migrated: [],
            };
            const return_json = {
                dry_run: {
                    fields,
                },
            };

            mockFetchSuccess(moveDryRunArtifact, { return_json });

            context.state.selected_tracker_id = 5;

            await moveDryRun(context, artifact_id);
            expect(context.commit).toHaveBeenCalledWith("switchToProcessingMove");
            expect(context.commit).toHaveBeenCalledWith("hasProcessedDryRun", fields);
            expect(context.commit).toHaveBeenCalledWith("resetProcessingMove");
            expect(context.commit).toHaveBeenCalledWith("blockArtifactMove");
            expect(redirectTo).not.toHaveBeenCalled();
        });

        it("When I process move in Dry run, if all field can be migrated, I process the move", async () => {
            const return_json = {
                dry_run: {
                    fields: {
                        fields_not_migrated: [],
                        fields_partially_migrated: [],
                        fields_migrated: [
                            {
                                field_id: 12,
                                label: "Fully migrated",
                                name: "fully_migrated",
                            },
                        ],
                    },
                },
            };

            mockFetchSuccess(moveDryRunArtifact, { return_json });
            moveArtifact.mockReturnValue(Promise.resolve());

            context.state.selected_tracker_id = tracker_id;

            await moveDryRun(context, artifact_id);

            expect(context.commit).toHaveBeenCalledWith("switchToProcessingMove");
            expect(moveArtifact).toHaveBeenCalledWith(artifact_id, tracker_id);
            expect(context.commit).toHaveBeenCalledWith("resetProcessingMove");
            expect(redirectTo).toHaveBeenCalledWith("/plugins/tracker/?aid=" + artifact_id);
        });
    });
});
