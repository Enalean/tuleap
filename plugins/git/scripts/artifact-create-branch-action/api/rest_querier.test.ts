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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { GitRepository } from "../src/types";
import { getProjectRepositories, postGitBranch } from "./rest_querier";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync } from "neverthrow";

describe("API querier", () => {
    describe("getProjectRepositories", () => {
        it("Given a project id then it will recursively get all project repositories", () => {
            const repositories = [{ id: 37 } as GitRepository, { id: 91 } as GitRepository];
            const tlpRecursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");
            tlpRecursiveGet.mockResolvedValue(repositories);

            const project_id = 27;
            getProjectRepositories(project_id, "acme");

            expect(tlpRecursiveGet).toHaveBeenCalledWith(
                "/api/v1/projects/27/git",
                expect.objectContaining({
                    params: {
                        fields: "basic",
                        limit: 50,
                        query: '{"scope":"project","allow_creation_of_branch":"acme"}',
                    },
                })
            );
        });
        it("asks to create the Git branch", async () => {
            const postSpy = jest.spyOn(fetch_result, "postJSON");
            postSpy.mockReturnValue(okAsync({} as Response));

            const repository_id = 27;
            const branch_name = "tuleap-123-title";
            const reference = "main";

            const result = await postGitBranch(repository_id, branch_name, reference);

            expect(postSpy).toHaveBeenCalledWith("/api/v1/git/27/branches", {
                branch_name: branch_name,
                reference: reference,
            });
            expect(result.isOk()).toBe(true);
        });
    });
});
