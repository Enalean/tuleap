/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { linkGitlabGroupWithTuleap } from "./tuleap-api-querier";
import { uri } from "@tuleap/fetch-result";

vi.mock("@tuleap/fetch-result");

describe("tuleap-api-querier", () => {
    it("should ask Tuleap to link the given group to the given project with given credentials", async () => {
        const postSpy = vi.spyOn(fetch_result, "postJSON");
        postSpy.mockReturnValue(okAsync(undefined));

        const project_id = 101;
        const gitlab_group_id = 818532;
        const gitlab_server_url = "https://example.com";
        const gitlab_token = "a1e2i3o4u5y6";
        const create_branch_prefix = "my-prefix";
        const allow_artifact_closure = true;

        const result = await linkGitlabGroupWithTuleap(
            project_id,
            gitlab_group_id,
            gitlab_server_url,
            gitlab_token,
            create_branch_prefix,
            allow_artifact_closure,
        );

        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/gitlab_groups`, {
            project_id,
            gitlab_group_id,
            gitlab_server_url,
            gitlab_token,
            create_branch_prefix,
            allow_artifact_closure,
        });
    });
});
