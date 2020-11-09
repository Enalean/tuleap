/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { formatRepository } from "./gitlab-repository-formatter";

describe("gitlabRepositoryFormatter", () => {
    describe("formatRepository", () => {
        it("Given a repo Gilab, Then it is formatted to be displayed in git", () => {
            const repo = {
                id: 1,
                gitlab_id: 1,
                name: "MyRepo",
                path: "MyPath/MyRepo",
                description: "This is my description.",
                full_url: "https://example.com/MyPath/MyRepo",
                last_push_date: "2020-10-28T15:13:13+01:00",
            };

            const repo_formatted = formatRepository(repo);

            expect(repo_formatted).toEqual({
                id: "gitlab_1",
                integration_id: 1,
                normalized_path: "MyPath/MyRepo",
                description: "This is my description.",
                path_without_project: "MyPath",
                label: "MyRepo",
                last_update_date: "2020-10-28T15:13:13+01:00",
                additional_information: [],
                gitlab_data: {
                    full_url: "https://example.com/MyPath/MyRepo",
                    gitlab_id: 1,
                },
            });
        });
    });
});
