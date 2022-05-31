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
import * as tlp from "@tuleap/tlp-fetch";
import {
    getGitLabRepositoryBranchInformation,
    GitLabBranchCreationPossibleError,
    postGitlabBranch,
    postGitlabMergeRequest,
} from "./rest-querier";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("postGitlabBranch", () => {
    it("asks to create the GitLab branch", async () => {
        const postSpy = jest.spyOn(tlp, "post");
        mockFetchSuccess(postSpy);

        const result = await postGitlabBranch(1, 123, "main");

        expect(postSpy).toHaveBeenCalledWith("/api/v1/gitlab_branch", {
            body: '{"gitlab_integration_id":1,"artifact_id":123,"reference":"main"}',
            headers: {
                "content-type": "application/json",
            },
        });
        expect(result.isOk()).toBe(true);
    });

    it("detects errors possibly caused by an invalid reference name", async () => {
        const postSpy = jest.spyOn(tlp, "post");
        mockFetchError(postSpy, {
            status: 400,
            error_json: { error: { message: "Invalid reference name: invalid_ref" } },
        });

        const result = await postGitlabBranch(1, 123, "invalid_ref");

        let error_type: string | null = null;
        if (result.isErr()) {
            error_type = (await result.error).error_type;
        }
        expect(error_type).toBe(GitLabBranchCreationPossibleError.INVALID_REF);
    });

    it("detects errors possibly caused by an already existing branch name", async () => {
        const postSpy = jest.spyOn(tlp, "post");
        mockFetchError(postSpy, {
            status: 400,
            error_json: { error: { message: "Branch already exists" } },
        });

        const result = await postGitlabBranch(1, 123, "invalid_ref");

        let error_type: string | null = null;
        if (result.isErr()) {
            error_type = (await result.error).error_type;
        }
        expect(error_type).toBe(GitLabBranchCreationPossibleError.BRANCH_ALREADY_EXIST);
    });

    it("does not mark all errors as caused by invalid reference name", async () => {
        const postSpy = jest.spyOn(tlp, "post");
        mockFetchError(postSpy, { status: 500 });

        const result = await postGitlabBranch(1, 123, "main");

        let error_type: string | null = null;
        if (result.isErr()) {
            error_type = (await result.error).error_type;
        }
        expect(error_type).toBe(GitLabBranchCreationPossibleError.UNKNOWN);
    });

    it("retrieves branch information of a GitLab integration", async () => {
        const getSpy = jest.spyOn(tlp, "get");

        mockFetchSuccess(getSpy);

        await getGitLabRepositoryBranchInformation(12);

        expect(getSpy).toHaveBeenCalledWith("/api/v1/gitlab_repositories/12/branches");
    });

    it("asks to create the GitLab merge request", async () => {
        const postSpy = jest.spyOn(tlp, "post");
        mockFetchSuccess(postSpy);

        const result = await postGitlabMergeRequest(1, 123, "prefix/tuleap-123");

        expect(postSpy).toHaveBeenCalledWith("/api/v1/gitlab_merge_request", {
            body: '{"gitlab_integration_id":1,"artifact_id":123,"source_branch":"prefix/tuleap-123"}',
            headers: {
                "content-type": "application/json",
            },
        });
        expect(result.isOk()).toBe(true);
    });
});
