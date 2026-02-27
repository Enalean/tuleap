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
import { describe, expect, it } from "vitest";
import {
    extendBranch,
    extendBranchForParent,
    canCreatePullrequest,
    getPullrequestUrl,
} from "./pullrequest-helper";

describe("pullrequest-helper", () => {
    describe("extendBranch()", () => {
        it("adds display_name, repository_id and project_id to a branch", () => {
            const branch = { name: "my-branch", other: "data" };
            const repository_id = 123;
            const project_id = 456;

            const extended = extendBranch(branch, repository_id, project_id);

            expect(extended).toEqual({
                name: "my-branch",
                other: "data",
                display_name: "my-branch",
                repository_id: 123,
                project_id: 456,
            });
        });
    });

    describe("extendBranchForParent()", () => {
        it("adds parent repository name to display_name and sets repository_id/project_id for branches in forks", () => {
            const branch = { name: "my-branch" };
            const parent_repository_id = 111;
            const parent_repository_name = "parent-repo";
            const parent_project_id = 222;

            const extended = extendBranchForParent(
                branch,
                parent_repository_id,
                parent_repository_name,
                parent_project_id,
            );

            expect(extended).toEqual({
                name: "my-branch",
                display_name: "parent-repo : my-branch",
                repository_id: 111,
                project_id: 222,
            });
        });
    });

    describe("canCreatePullrequest()", () => {
        it("returns true if both source and destination branches are available and not unique", () => {
            const source_branches = [
                { name: "b1", display_name: "b1", repository_id: 1, project_id: 1 },
                { name: "b2", display_name: "b2", repository_id: 1, project_id: 1 },
            ];
            const destination_branches = [
                { name: "b3", display_name: "b3", repository_id: 1, project_id: 1 },
            ];

            expect(canCreatePullrequest(source_branches, destination_branches)).toBe(true);
        });

        it("returns false if there is only one source branch and one destination branch", () => {
            const source_branches = [
                { name: "b1", display_name: "b1", repository_id: 1, project_id: 1 },
            ];
            const destination_branches = [
                { name: "b2", display_name: "b2", repository_id: 1, project_id: 1 },
            ];

            expect(canCreatePullrequest(source_branches, destination_branches)).toBe(false);
        });

        it("returns false if either list is empty", () => {
            expect(
                canCreatePullrequest(
                    [],
                    [{ name: "b1", display_name: "b1", repository_id: 1, project_id: 1 }],
                ),
            ).toBe(false);
            expect(
                canCreatePullrequest(
                    [{ name: "b1", display_name: "b1", repository_id: 1, project_id: 1 }],
                    [],
                ),
            ).toBe(false);
        });
    });

    describe("getPullrequestUrl()", () => {
        it("builds the correct pull request overview URL", () => {
            const pullrequest_id = 789;
            const repository_id = 123;
            const project_id = 456;

            const url = getPullrequestUrl(pullrequest_id, repository_id, project_id);

            expect(url).toBe(
                "/plugins/git/?action=pull-requests&tab=overview&repo_id=123&group_id=456#/pull-requests/789/overview",
            );
        });
    });
});
