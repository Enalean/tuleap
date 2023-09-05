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

import * as actions from "./actions.js";
import * as rest_querier from "../api/rest-querier.js";
import * as window_helper from "../helpers/window-helper.js";

describe("Store actions", () => {
    let context;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
        };
    });

    describe("init", () => {
        it("loads branches of current repository and store them as source branches", async () => {
            const branches = [{ name: "master" }, { name: "feature/branch" }];
            const getBranches = jest.spyOn(rest_querier, "getBranches");
            getBranches.mockReturnValue(Promise.resolve(branches));

            await actions.init(context, { repository_id: 42, project_id: 101 });

            expect(getBranches).toHaveBeenCalledWith(42);
            expect(context.commit).toHaveBeenCalledWith("setSourceBranches", [
                { display_name: "master", repository_id: 42, project_id: 101, name: "master" },
                {
                    display_name: "feature/branch",
                    repository_id: 42,
                    project_id: 101,
                    name: "feature/branch",
                },
            ]);
        });

        it("loads branches of current repository and store them as destination branches if there is no parent repository", async () => {
            const branches = [{ name: "master" }, { name: "feature/branch" }];
            const getBranches = jest.spyOn(rest_querier, "getBranches");
            getBranches.mockReturnValue(Promise.resolve(branches));

            await actions.init(context, { repository_id: 42, project_id: 101 });

            expect(context.commit).toHaveBeenCalledWith("setDestinationBranches", [
                { display_name: "master", repository_id: 42, project_id: 101, name: "master" },
                {
                    display_name: "feature/branch",
                    repository_id: 42,
                    project_id: 101,
                    name: "feature/branch",
                },
            ]);
        });

        it("loads branches of parent repository and add them as destination branches", async () => {
            const branches = [{ name: "master" }, { name: "feature/branch" }];
            const parent_branches = [{ name: "master" }, { name: "dev" }];
            jest.spyOn(rest_querier, "getBranches").mockImplementation((id) => {
                if (id === 42) {
                    return Promise.resolve(branches);
                }
                if (id === 66) {
                    return Promise.resolve(parent_branches);
                }
                throw new Error("Unexpected ID: " + id);
            });

            await actions.init(context, {
                repository_id: 42,
                project_id: 101,
                parent_repository_id: 66,
                parent_project_id: 102,
                parent_repository_name: "ledepot",
                user_can_see_parent_repository: true,
            });

            expect(context.commit).toHaveBeenCalledWith("setDestinationBranches", [
                { display_name: "master", repository_id: 42, project_id: 101, name: "master" },
                {
                    display_name: "feature/branch",
                    repository_id: 42,
                    project_id: 101,
                    name: "feature/branch",
                },
                {
                    display_name: "ledepot : master",
                    repository_id: 66,
                    project_id: 102,
                    name: "master",
                },
                { display_name: "ledepot : dev", repository_id: 66, project_id: 102, name: "dev" },
            ]);
        });

        it("switch the error flag if REST API returns an error", async () => {
            jest.spyOn(rest_querier, "getBranches").mockReturnValue(Promise.reject(500));

            await actions.init(context, {
                repository_id: 42,
                project_id: 101,
                parent_repository_id: 66,
                parent_project_id: 102,
                parent_repository_name: "ledepot",
            });

            expect(context.commit).toHaveBeenCalledWith("setHasErrorWhileLoadingBranchesToTrue");
        });

        it("loads branches of current repository and store them as destination branches if user can't access parent repository", async () => {
            const branches = [{ name: "master" }, { name: "feature/branch" }];
            const getBranches = jest.spyOn(rest_querier, "getBranches");
            getBranches.mockReturnValue(Promise.resolve(branches));

            await actions.init(context, {
                repository_id: 42,
                project_id: 101,
                parent_repository_id: 66,
                parent_project_id: 102,
                parent_repository_name: "ledepot",
                user_can_see_parent_repository: false,
            });

            expect(context.commit).toHaveBeenCalledWith("setDestinationBranches", [
                { display_name: "master", repository_id: 42, project_id: 101, name: "master" },
                {
                    display_name: "feature/branch",
                    repository_id: 42,
                    project_id: 101,
                    name: "feature/branch",
                },
            ]);
        });
    });

    describe("create", () => {
        const source_branch = {
            repository_id: 102,
            project_id: 42,
            name: "feature/branch",
        };
        const destination_branch = {
            repository_id: 101,
            project_id: 42,
            name: "master",
        };

        it("calls the rest api to create the pull request", async () => {
            const created_pullrequest = { id: 1 };
            const createPullrequest = jest.spyOn(rest_querier, "createPullrequest");
            createPullrequest.mockReturnValue(Promise.resolve(created_pullrequest));

            jest.spyOn(window_helper, "redirectTo").mockImplementation(() => {});

            await actions.create(context, { source_branch, destination_branch });

            expect(createPullrequest).toHaveBeenCalledWith(102, "feature/branch", 101, "master");
        });

        it("does a full page reload to redirect to the created pull request", async () => {
            const created_pullrequest = { id: 1 };
            const createPullrequest = jest.spyOn(rest_querier, "createPullrequest");
            createPullrequest.mockReturnValue(Promise.resolve(created_pullrequest));

            const redirectTo = jest.spyOn(window_helper, "redirectTo").mockImplementation(() => {});

            await actions.create(context, { source_branch, destination_branch });

            expect(redirectTo).toHaveBeenCalledWith(
                "/plugins/git/?action=pull-requests&tab=overview&repo_id=101&group_id=42#/pull-requests/1/overview",
            );
        });

        it("Logs an error if the creation failed", async () => {
            const createPullrequest = jest.spyOn(rest_querier, "createPullrequest");
            createPullrequest.mockReturnValue(
                Promise.reject({
                    response: {
                        json() {
                            return Promise.resolve({
                                error: {
                                    message: "You cannot create this pullrequest",
                                },
                            });
                        },
                    },
                }),
            );

            await actions.create(context, { source_branch, destination_branch });

            expect(createPullrequest).toHaveBeenCalledWith(102, "feature/branch", 101, "master");
            expect(context.commit).toHaveBeenCalledWith(
                "setCreateErrorMessage",
                "You cannot create this pullrequest",
            );
        });
    });
});
