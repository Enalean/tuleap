/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import * as rest_querier from "./api/rest-querier";
import type { GitlabIntegration } from "./fetch-gitlab-repositories-information";
import { getGitlabRepositoriesWithDefaultBranches } from "./fetch-gitlab-repositories-information";
import { Fault } from "@tuleap/fault";
import { errAsync, okAsync } from "neverthrow";

function buildFakeGitLabIntegration(id: number): GitlabIntegration {
    return {
        id: id,
        gitlab_repository_url: `https://repo${id}.example.com`,
        name: `Repo${id}`,
        create_branch_prefix: "",
    };
}

describe("fetch-gitlab-repositories-information", () => {
    it("retrieves default branches and ignore failures", async (): Promise<void> => {
        const repo_1 = buildFakeGitLabIntegration(1);
        const repo_2 = buildFakeGitLabIntegration(2);

        const spyGetBranchInfo = vi.spyOn(rest_querier, "getGitLabRepositoryBranchInformation");
        spyGetBranchInfo.mockImplementation((integration_id: number) => {
            if (integration_id === 1) {
                return okAsync({ ...repo_1, default_branch: "dev" });
            }
            if (integration_id === 2) {
                return errAsync(
                    Fault.fromMessage(
                        "Something bad happened while retrieving the branches information",
                    ),
                );
            }

            throw new Error(`Integration #${integration_id} was not expected`);
        });

        const integrations_with_default_branch = await getGitlabRepositoriesWithDefaultBranches([
            repo_1,
            repo_2,
        ]);
        expect(integrations_with_default_branch).toStrictEqual([
            { ...repo_1, default_branch: "dev" },
            { ...repo_2, default_branch: "" },
        ]);
    });
});
