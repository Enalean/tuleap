/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import type { GitRepository } from "../src/types";
import {
    getProjectRepositories,
    postGitBranch,
    postPullRequestOnDefaultBranch,
} from "./rest_querier";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync } from "neverthrow";
import { uri } from "@tuleap/fetch-result";

vi.mock("@tuleap/fetch-result");

describe("API querier", () => {
    describe("getProjectRepositories", () => {
        it("Given a project id then it will recursively get all project repositories", async () => {
            const repositories = [{ id: 37 } as GitRepository, { id: 91 } as GitRepository];
            const getAllJSON = vi
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync(repositories));

            const project_id = 27;
            const result = await getProjectRepositories(project_id, "acme");

            expect(getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/projects/27/git`,
                expect.objectContaining({
                    params: {
                        fields: "basic",
                        limit: 50,
                        query: '{"scope":"project","allow_creation_of_branch":"acme"}',
                    },
                }),
            );
            expect(result.isOk()).toBe(true);
        });
    });
    describe("postGitBranch", () => {
        it("asks to create the Git branch", async () => {
            const postSpy = vi.spyOn(fetch_result, "postJSON");
            postSpy.mockReturnValue(okAsync({ html_url: "URL" }));

            const repository_id = 27;
            const branch_name = "tuleap-123-title";
            const reference = "main";

            const result = await postGitBranch(repository_id, branch_name, reference);

            expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/git/27/branches`, {
                branch_name: branch_name,
                reference: reference,
            });
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }
            expect(result.value.html_url).toBe("URL");
        });
    });
    describe("postPullRequestOnDefaultBranch", () => {
        it("asks to create the Git branch", async () => {
            const postSpy = vi.spyOn(fetch_result, "postJSON");
            postSpy.mockReturnValue(okAsync({ id: 123 }));

            const repository: GitRepository = {
                id: 27,
                default_branch: "main",
            } as GitRepository;

            const result = await postPullRequestOnDefaultBranch(repository, "tuleap-123-title");

            expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests`, {
                repository_id: 27,
                repository_dest_id: 27,
                branch_src: "tuleap-123-title",
                branch_dest: "main",
            });
            expect(result.isOk()).toBe(true);
        });
    });
});
