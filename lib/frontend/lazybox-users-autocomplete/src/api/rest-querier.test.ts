/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import type { User } from "@tuleap/core-rest-api-types";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { fetchMatchingUsers } from "./rest-querier";

describe("tuleap-rest-querier", () => {
    describe("fetchMatchingUsers", () => {
        it("Given a query, Then it should fetch the matching users", async () => {
            const users = [{ id: 101, display_name: "Joe l'Asticot" } as User];

            vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(users));

            const query = "Joe l'A";
            const result = await fetchMatchingUsers(query);
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(fetch_result.getJSON).toHaveBeenCalledWith(uri`/api/v1/users`, {
                params: {
                    query,
                    limit: 10,
                    offset: 0,
                },
            });

            expect(result.value).toStrictEqual(users);
        });
    });
});
