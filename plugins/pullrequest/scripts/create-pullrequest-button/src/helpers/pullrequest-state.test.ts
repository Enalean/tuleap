/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import { describe, it, expect, vi } from "vitest";
import { buildPullrequestState } from "./pullrequest-state";
import * as rest from "../api/rest-querier";
import * as window_helper from "./window-helper";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { createPullrequest } from "../api/rest-querier";
import type { ExtendedBranch } from "./pullrequest-helper";

describe("buildPullrequestState", () => {
    const repository_id = 1;
    const project_id = 101;
    const parent_repository_id = 2;
    const parent_project_id = 202;
    const parent_repository_name = "parent-repo";

    function buildExtendedBranch(name: string, repo: number, project: number): ExtendedBranch {
        return {
            name,
            display_name: name,
            repository_id: repo,
            project_id: project,
        };
    }

    describe("init()", () => {
        it("loads source and destination branches for regular repositories", async () => {
            vi.spyOn(rest, "getBranches").mockResolvedValue([
                { name: "feature" },
                { name: "main" },
            ]);

            const state = buildPullrequestState();

            await state.init({
                repository_id,
                project_id,
                parent_repository_id: 0,
                parent_repository_name: "",
                parent_project_id: 0,
                user_can_see_parent_repository: false,
            });

            expect(state.source_branches.value).toHaveLength(2);
            expect(state.destination_branches.value).toHaveLength(2);
        });

        it("loads fork repository branches when visible, then local branches", async () => {
            vi.spyOn(rest, "getBranches").mockResolvedValue([
                { name: "parent-branch" },
                { name: "local-branch" },
            ]);

            const state = buildPullrequestState();

            await state.init({
                repository_id,
                project_id,
                parent_repository_id,
                parent_repository_name,
                parent_project_id,
                user_can_see_parent_repository: true,
            });

            expect(state.destination_branches.value).toHaveLength(4);
            expect(state.destination_branches.value[0].name).toBe("parent-branch");
            expect(state.destination_branches.value[0].repository_id).toBe(parent_repository_id);
            expect(state.destination_branches.value[1].name).toBe("local-branch");
            expect(state.destination_branches.value[1].repository_id).toBe(parent_repository_id);
            expect(state.destination_branches.value[2].name).toBe("parent-branch");
            expect(state.destination_branches.value[2].repository_id).toBe(repository_id);
            expect(state.destination_branches.value[3].name).toBe("local-branch");
            expect(state.destination_branches.value[3].repository_id).toBe(repository_id);
        });

        it("sets error flag and rethrows when getBranches fails", async () => {
            vi.spyOn(rest, "getBranches").mockRejectedValue(new Error("Network error"));

            const state = buildPullrequestState();

            await expect(
                state.init({
                    repository_id,
                    project_id,
                    parent_repository_id,
                    parent_repository_name,
                    parent_project_id,
                    user_can_see_parent_repository: true,
                }),
            ).rejects.toThrow("Network error");

            expect(state.has_error_while_loading_branches.value).toBe(true);
        });
    });

    describe("create()", () => {
        it("does nothing when source or destination branch is not selected", async () => {
            const state = buildPullrequestState();

            const mock_querier = vi.spyOn(rest, "createPullrequest");

            await state.create();

            expect(mock_querier).not.toHaveBeenCalled();
        });

        it("creates a pull request and redirects", async () => {
            const source = buildExtendedBranch("feature", 1, 101);
            const dest = buildExtendedBranch("main", 1, 101);

            const state = buildPullrequestState();
            state.selected_source_branch.value = source;
            state.selected_destination_branch.value = dest;

            vi.spyOn(rest, "createPullrequest").mockResolvedValue({ id: 999 });
            const mock_redirect = vi.spyOn(window_helper, "redirectTo");

            await state.create();

            expect(createPullrequest).toHaveBeenCalledWith(1, "feature", 1, "main");
            expect(mock_redirect).toHaveBeenCalledWith(
                "/plugins/git/?action=pull-requests&tab=overview&repo_id=1&group_id=101#/pull-requests/999/overview",
            );
        });

        it("handles error", async () => {
            const source = buildExtendedBranch("feature", 1, 101);
            const dest = buildExtendedBranch("main", 1, 101);

            const state = buildPullrequestState();
            state.selected_source_branch.value = source;
            state.selected_destination_branch.value = dest;

            const error_repsonse = {
                json: vi.fn().mockResolvedValue({
                    error: { message: "Invalid branch" },
                }),
            };

            const fetch_error = new FetchWrapperError(
                "fail",
                error_repsonse as unknown as Response,
            );

            vi.spyOn(rest, "createPullrequest").mockRejectedValue(fetch_error);

            await expect(state.create()).rejects.toThrow(FetchWrapperError);

            expect(state.create_error_message.value).toBe("Invalid branch");
            expect(state.is_creating_pullrequest.value).toBe(false);
        });
    });

    describe("resetSelection()", () => {
        it("resets selected branches and clears error message", () => {
            const state = buildPullrequestState();

            state.selected_source_branch.value = buildExtendedBranch("a", 1, 1);
            state.selected_destination_branch.value = buildExtendedBranch("b", 1, 1);
            state.create_error_message.value = "Something went wrong";

            state.resetSelection();

            expect(state.selected_source_branch.value).toBe("");
            expect(state.selected_destination_branch.value).toBe("");
            expect(state.create_error_message.value).toBe("");
        });
    });
});
