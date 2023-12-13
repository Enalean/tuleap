/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import { uri } from "@tuleap/fetch-result";
import * as fetch_result from "@tuleap/fetch-result";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { fetchAllPullRequests } from "./tuleap-rest-querier";

const repository_id = 10;
const pull_requests_collection = [
    {
        collection: [{ id: 1 } as PullRequest, { id: 2 } as PullRequest, { id: 3 } as PullRequest],
    },
];

describe("tuleap-rest-querier", () => {
    describe("fetchAllPullRequests", () => {
        it("should query all the pull-requests of a given repository and return them", async () => {
            vi.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(pull_requests_collection));

            const result = await fetchAllPullRequests(repository_id);
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }

            expect(fetch_result.getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/git/${repository_id}/pull_requests`,
                {
                    params: {
                        limit: 50,
                    },
                    getCollectionCallback: expect.any(Function),
                },
            );

            expect(result.value).toStrictEqual(pull_requests_collection);
        });
    });
});
