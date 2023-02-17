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
import { fetchPullRequestInfo, fetchUserInfo } from "./tuleap-rest-querier";

vi.mock("@tuleap/fetch-result");

describe("tuleap-rest-querier", () => {
    describe("fetchPullRequestInfo()", () => {
        it("Given the current pull request id, then it should fetch its info", async () => {
            const pull_request_id = "50";
            const pull_request_info = {
                title: "My pull request title",
            };

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(pull_request_info));
            const result = await fetchPullRequestInfo(pull_request_id);

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(
                uri`/api/v1/pull_requests/${pull_request_id}`
            );
            expect(result.value).toStrictEqual(pull_request_info);
        });
    });

    describe("fetchUserInfo()", () => {
        it("Given an user id, then it should fetch its info", async () => {
            const user_id = 102;
            const user_info = {
                display_name: "Joe l'asticot",
            };

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(user_info));
            const result = await fetchUserInfo(user_id);

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(uri`/api/v1/users/${user_id}`);
            expect(result.value).toStrictEqual(user_info);
        });
    });
});
