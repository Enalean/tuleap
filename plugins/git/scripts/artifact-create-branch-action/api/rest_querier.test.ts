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
import type { RecursiveGetProjectRepositories } from "./rest_querier";
import { getProjectRepositories } from "./rest_querier";

describe("API querier", () => {
    describe("getProjectRepositories", () => {
        it("Given a project id then it will recursively get all project repositories", () => {
            const repositories = [{ id: 37 } as GitRepository, { id: 91 } as GitRepository];
            const tlpRecursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");
            const response: RecursiveGetProjectRepositories[] = [
                {
                    repositories: repositories,
                },
            ];
            tlpRecursiveGet.mockResolvedValue(response);

            const project_id = 27;
            getProjectRepositories(project_id);

            expect(tlpRecursiveGet).toHaveBeenCalledWith(
                "/api/v1/projects/27/git",
                expect.objectContaining({
                    params: {
                        fields: "basic",
                        limit: 50,
                    },
                })
            );
        });
    });
});
