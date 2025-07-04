/*
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

import { describe, expect, it } from "vitest";
import { QueryRetriever } from "./QueryRetriever";
import { formatDatetimeToYearMonthDay } from "@tuleap/plugin-timetracking-time-formatters";
import type { User } from "@tuleap/core-rest-api-types";

describe("QueryRetriever", () => {
    describe("setQuery", () => {
        it("should sort users", () => {
            const start_date = formatDatetimeToYearMonthDay(new Date());
            const end_date = formatDatetimeToYearMonthDay(new Date());

            const users: User[] = [
                {
                    id: 1858,
                    user_url: "/users/alice.hernandez",
                    display_name: "Alice Hernandez (alice.hernandez)",
                    avatar_url: "/avatar-ea78.png",
                },
                {
                    id: 6871,
                    user_url: "/users/bobby.arnold",
                    display_name: "Bobby Arnold (bobby.arnold)",
                    avatar_url: "/avatar-2129.png",
                },
                {
                    id: 7964,
                    user_url: "/users/alyssa.buchanan",
                    display_name: "Alyssa Buchanan (alyssa.buchanan)",
                    avatar_url: "/avatar-77a6.png",
                },
            ];

            const query_retriever = QueryRetriever();
            query_retriever.setQuery(start_date, end_date, "", users);

            expect(
                query_retriever.getQuery().users_list.map((user) => user.display_name),
            ).toStrictEqual([
                "Alice Hernandez (alice.hernandez)",
                "Alyssa Buchanan (alyssa.buchanan)",
                "Bobby Arnold (bobby.arnold)",
            ]);
        });
    });
});
