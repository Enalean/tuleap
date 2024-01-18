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
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import {
    fetchAllPullRequests,
    fetchPullRequestLabels,
    fetchPullRequestsAuthors,
} from "./tuleap-rest-querier";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { UserStub } from "../../tests/stubs/UserStub";

const repository_id = 10;
const pull_request_id = 2;
const pull_requests_collection = [
    {
        collection: [
            PullRequestStub.buildOpenPullRequest({ id: 1 }),
            PullRequestStub.buildOpenPullRequest({ id: 2 }),
            PullRequestStub.buildOpenPullRequest({ id: 3 }),
        ],
    },
];

const labels_collection = [
    {
        labels: [
            { id: 1, label: "Salade", is_outline: true, color: "neon-green" },
            { id: 2, label: "Tomates", is_outline: true, color: "fiesta-red" },
            { id: 3, label: "Oignons", is_outline: false, color: "plum-crazy" },
        ],
    },
];

const users_collection: User[] = [
    UserStub.withIdAndName(101, "Joe l'asticot (jolasti)"),
    UserStub.withIdAndName(102, "John Doe (jdoe)"),
    UserStub.withIdAndName(5, "Johann Zarco (jz5)"),
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

    describe("fetchPullRequestLabels", () => {
        it("should query all the labels of a given pull-request and return them", async () => {
            vi.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(labels_collection));

            const result = await fetchPullRequestLabels(pull_request_id);
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }

            expect(fetch_result.getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/labels`,
                {
                    params: {
                        limit: 50,
                    },
                    getCollectionCallback: expect.any(Function),
                },
            );

            expect(result.value).toStrictEqual(labels_collection);
        });
    });

    describe("fetchPullRequestsAuthors", () => {
        it("should query all the pull-requests authors in a given repository", async () => {
            vi.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(users_collection));

            const result = await fetchPullRequestsAuthors(repository_id);
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }

            expect(fetch_result.getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/git/${repository_id}/pull_requests_authors`,
                {
                    params: {
                        limit: 50,
                    },
                },
            );

            expect(result.value).toStrictEqual(users_collection);
        });
    });
});
