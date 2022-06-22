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
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync } from "neverthrow";
import {
    getGitLabRepositoryBranchInformation,
    postGitlabBranch,
    postGitlabMergeRequest,
} from "./rest-querier";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

const GITLAB_INTEGRATION_ID = 12;

const ARTIFACT_ID = 123;
describe(`rest-querier`, () => {
    describe("postGitlabBranch", () => {
        it("asks to create the GitLab branch", async () => {
            const postSpy = jest.spyOn(tlp, "post");
            mockFetchSuccess(postSpy);

            const result = await postGitlabBranch(1, ARTIFACT_ID, "main");

            expect(postSpy).toHaveBeenCalledWith("/api/v1/gitlab_branch", {
                body: '{"gitlab_integration_id":1,"artifact_id":123,"reference":"main"}',
                headers: {
                    "content-type": "application/json",
                },
            });
            expect(result.isOk()).toBe(true);
        });

        it("displays the i18n_error", async () => {
            const postSpy = jest.spyOn(tlp, "post");

            mockFetchError(postSpy, {
                status: 400,
                error_json: {
                    error: { i18n_error_message: "Invalid reference name: invalid_ref" },
                },
            });

            const result = await postGitlabBranch(1, ARTIFACT_ID, "invalid_ref");

            let error_message: string | undefined;
            if (result.isErr()) {
                error_message = (await result.error).i18n_error_message;
            }
            expect(error_message).toBe("Invalid reference name: invalid_ref");
        });

        it("display not internationalized errors", async () => {
            const postSpy = jest.spyOn(tlp, "post");
            mockFetchError(postSpy, { status: 500, error_json: { error: { message: "Oh snap" } } });

            const result = await postGitlabBranch(1, ARTIFACT_ID, "main");

            let error_message;
            if (result.isErr()) {
                error_message = (await result.error).error_message;
            }

            expect(error_message).toStrictEqual({ message: "Oh snap" });
        });
    });

    it("asks to create the GitLab merge request", async () => {
        const postSpy = jest.spyOn(fetch_result, "postJSON");
        postSpy.mockReturnValue(okAsync({} as Response));

        const result = await postGitlabMergeRequest(
            GITLAB_INTEGRATION_ID,
            ARTIFACT_ID,
            "prefix/tuleap-123"
        );

        expect(postSpy).toHaveBeenCalledWith("/api/v1/gitlab_merge_request", {
            gitlab_integration_id: GITLAB_INTEGRATION_ID,
            artifact_id: ARTIFACT_ID,
            source_branch: "prefix/tuleap-123",
        });
        expect(result.isOk()).toBe(true);
    });

    describe(`getGitLabRepositoryBranchInformation()`, () => {
        it(`retrieves branch information of a GitLab integration`, async () => {
            const getSpy = jest.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync({ default_branch: "main" }));

            const result = await getGitLabRepositoryBranchInformation(GITLAB_INTEGRATION_ID);

            expect(getSpy).toHaveBeenCalledWith(
                `/api/v1/gitlab_repositories/${GITLAB_INTEGRATION_ID}/branches`
            );
            expect(result.isOk()).toBe(true);
        });
    });
});
