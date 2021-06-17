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
import { postGitlabBranch } from "./rest-querier";

jest.mock("@tuleap/tlp-fetch");

describe("postGitlabBranch", () => {
    it("asks to create the GitLab branch", async () => {
        const postSpy = jest.spyOn(tlp, "post");

        await postGitlabBranch(1, 123, "dev_TULEAP-123", "main");

        expect(postSpy).toHaveBeenCalledWith("/api/v1/gitlab_branch", {
            body: '{"gitlab_integration_id":1,"artifact_id":123,"branch_name":"dev_TULEAP-123","reference":"main"}',
            headers: {
                "content-type": "application/json",
            },
        });
    });
});
