/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { getGitPermissions } from "./rest-querier";
import type { RepositoryFineGrainedPermissions } from "./type";

describe("API querier", () => {
    describe("getGitPermissions", () => {
        it("Given a project id and empty group id, Then it will get permission for git", async () => {
            const project_id = 101;

            const tlpGet = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(tlpGet, {
                headers: {
                    // X-PAGINATION-SIZE
                    get: (): string => "2",
                },
                return_json: {
                    repositories: [{ name: "repo" } as RepositoryFineGrainedPermissions],
                },
            });

            const result = await getGitPermissions(project_id, "");

            expect(tlpGet).toHaveBeenCalledWith(
                "/plugins/git/",
                expect.objectContaining({
                    params: {
                        group_id: project_id,
                        selected_ugroup_id: "",
                        action: "permission-per-group",
                    },
                }),
            );

            expect(result).toEqual({ repositories: [{ name: "repo" }] });
        });
    });
});
