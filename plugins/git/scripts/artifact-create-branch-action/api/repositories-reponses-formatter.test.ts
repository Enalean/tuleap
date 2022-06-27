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

import type { RecursiveGetProjectRepositories } from "./rest_querier";
import type { GitRepository } from "../src/types";
import { formatProjectRepositoriesResponseAsArray } from "./repositories-reponses-formatter";

describe("Project Repositories response formatter", () => {
    describe("formatProjectRepositoriesResponseAsArray", () => {
        it("formats a response will all results stored in one response", () => {
            const project_repositories_responses: RecursiveGetProjectRepositories[] = [
                {
                    repositories: [{ id: 37 } as GitRepository, { id: 91 } as GitRepository],
                },
            ];

            const formatted_response = formatProjectRepositoriesResponseAsArray(
                project_repositories_responses
            );

            expect(formatted_response).toStrictEqual([
                { id: 37 } as GitRepository,
                { id: 91 } as GitRepository,
            ]);
        });
        it("formats a response will all results stored in multiple responses", () => {
            const project_repositories_responses: RecursiveGetProjectRepositories[] = [
                {
                    repositories: [{ id: 37 } as GitRepository],
                },
                {
                    repositories: [{ id: 91 } as GitRepository],
                },
            ];

            const formatted_response = formatProjectRepositoriesResponseAsArray(
                project_repositories_responses
            );

            expect(formatted_response).toStrictEqual([
                { id: 37 } as GitRepository,
                { id: 91 } as GitRepository,
            ]);
        });
    });
});
