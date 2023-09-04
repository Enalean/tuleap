/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync } from "neverthrow";
import {
    getGitLabRepositoryBranchInformation,
    postGitlabBranch,
    postGitlabMergeRequest,
} from "./rest-querier";
import { uri } from "@tuleap/fetch-result";

const GITLAB_INTEGRATION_ID = 12;
const ARTIFACT_ID = 123;
const MAIN_BRANCH = "main";

vi.mock("@tuleap/fetch-result");

describe(`rest-querier`, () => {
    it("asks to create the GitLab branch", async () => {
        const postSpy = vi.spyOn(fetch_result, "postJSON");
        postSpy.mockReturnValue(okAsync({ branch_name: MAIN_BRANCH }));

        const result = await postGitlabBranch(GITLAB_INTEGRATION_ID, ARTIFACT_ID, MAIN_BRANCH);

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/gitlab_branch`, {
            gitlab_integration_id: GITLAB_INTEGRATION_ID,
            artifact_id: ARTIFACT_ID,
            reference: MAIN_BRANCH,
        });
        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }
        expect(result.value.branch_name).toBe(MAIN_BRANCH);
    });

    it("asks to create the GitLab merge request", async () => {
        const postSpy = vi.spyOn(fetch_result, "post");
        postSpy.mockReturnValue(okAsync({} as Response));

        const result = await postGitlabMergeRequest(
            GITLAB_INTEGRATION_ID,
            ARTIFACT_ID,
            "prefix/tuleap-123",
        );

        expect(postSpy).toHaveBeenCalledWith(
            uri`/api/v1/gitlab_merge_request`,
            {},
            {
                gitlab_integration_id: GITLAB_INTEGRATION_ID,
                artifact_id: ARTIFACT_ID,
                source_branch: "prefix/tuleap-123",
            },
        );
        expect(result.isOk()).toBe(true);
    });

    describe(`getGitLabRepositoryBranchInformation()`, () => {
        it(`retrieves branch information of a GitLab integration`, async () => {
            const getSpy = vi.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync({ default_branch: "main" }));

            const result = await getGitLabRepositoryBranchInformation(GITLAB_INTEGRATION_ID);

            expect(getSpy).toHaveBeenCalledWith(
                uri`/api/v1/gitlab_repositories/${GITLAB_INTEGRATION_ID}/branches`,
            );
            expect(result.isOk()).toBe(true);
        });
    });
});
