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
import { putQuery } from "./rest-querier";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync } from "neverthrow";
import { uri } from "@tuleap/fetch-result";
import type { Query } from "../type";
import type { User } from "@tuleap/core-rest-api-types";

const widget_id = 47;
const start_date = "2024-08-02T00:00:00Z";
const end_date = "2024-09-01T00:00:00Z";
const users = [{ id: 101 } as User, { id: 102 } as User];

const query: Query = {
    start_date: start_date,
    end_date: end_date,
    predefined_time_period: "",
    users_list: users,
};

describe("rest-querier", () => {
    describe("putQuery", () => {
        it("should update the query with dates", async () => {
            vi.spyOn(fetch_result, "putJSON").mockReturnValue(okAsync(true));

            const result = await putQuery(widget_id, query);

            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }

            expect(fetch_result.putJSON).toHaveBeenCalledWith(
                uri`/api/v1/timetracking_people_widget/${widget_id}`,
                {
                    start_date: start_date,
                    end_date: end_date,
                    predefined_time_period: null,
                    users: users,
                },
            );
        });
        it("should update the query with predefined time period", async () => {
            vi.spyOn(fetch_result, "putJSON").mockReturnValue(okAsync(true));

            query.predefined_time_period = "today";

            const result = await putQuery(widget_id, query);

            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }

            expect(fetch_result.putJSON).toHaveBeenCalledWith(
                uri`/api/v1/timetracking_people_widget/${widget_id}`,
                {
                    start_date: null,
                    end_date: null,
                    predefined_time_period: "today",
                    users: users,
                },
            );
        });
    });
});
