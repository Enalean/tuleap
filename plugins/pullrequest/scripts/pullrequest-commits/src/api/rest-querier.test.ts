/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import { fetchPullRequestCommits, fetchPullRequestInfo } from "./rest-querier";
import { CommitStub } from "../../tests/stubs/CommitStub";

vi.mock("@tuleap/fetch-result");

const pull_request_id = 50;

describe("rest-querier", () => {
    describe("fetchPullRequestInfo()", () => {
        it("Given the current pull request id, then it should fetch its info", async () => {
            const pull_request_info = {
                title: "My pull request title",
            };

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(pull_request_info));
            const result = await fetchPullRequestInfo(pull_request_id);

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}`,
            );
            expect(result.value).toStrictEqual(pull_request_info);
        });
    });

    describe("fetchPullRequestCommits", () => {
        it("Given the current pull request id, then it should fetch its commits", async () => {
            const commits = [
                CommitStub.fromUnknownAuthor("d8fb8fc8e9d384402eec582fe504eae109f6fc9a", {
                    author_name: "John Doe",
                    author_email: "john.doe@example.com",
                }),
                CommitStub.fromUnknownAuthor("f9b6ec23a9c1a1e8989a5dca335a86b435000d85", {
                    author_name: "John Doe",
                    author_email: "john.doe@example.com",
                }),
            ];

            vi.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(commits));

            const result = await fetchPullRequestCommits(pull_request_id);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getAllJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}/commits`,
                expect.any(Object),
            );

            expect(result.value).toStrictEqual(commits);
        });
    });
});
