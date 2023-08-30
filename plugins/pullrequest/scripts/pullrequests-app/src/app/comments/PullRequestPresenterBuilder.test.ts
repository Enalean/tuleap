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

import { PullRequestPresenterBuilder } from "./PullRequestPresenterBuilder";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

describe("PullRequestPresenterBuilder", () => {
    it("should build from a pull request representation", () => {
        const pull_request = {
            id: 144,
            title: "Add feature flag",
            repository: {
                project: {
                    id: 105,
                },
            },
        } as PullRequest;

        expect(PullRequestPresenterBuilder.fromPullRequest(pull_request)).toStrictEqual({
            pull_request_id: 144,
            project_id: 105,
        });
    });
});
