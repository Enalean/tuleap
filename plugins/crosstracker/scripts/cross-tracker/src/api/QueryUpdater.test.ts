/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, it, vi, expect } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import type { Query } from "../type";
import type { PutQueryRepresentation } from "./cross-tracker-rest-api-types";
import { QueryUpdater } from "./QueryUpdater";

describe("QueryUpdater", () => {
    describe("updateQuery()", () => {
        const tql_query = "SELECT @id, @project.name FROM @project = MY_PROJECTS() WHERE @id > 2";
        const current_query: Query = {
            id: "0194d59b-f37b-73e1-a553-cf143a3c1203",
            tql_query,
            title: "My current TQL query",
            description: "My current description",
            is_default: false,
        };
        const updated_query: PutQueryRepresentation = {
            widget_id: 4,
            tql_query,
            title: "My updated TQL query",
            description: "My updated description",
            is_default: true,
        };

        it("should send the query to the REST API and return the just updated query", async () => {
            const putJSON = vi.spyOn(fetch_result, "putJSON").mockReturnValue(
                okAsync({
                    id: current_query.id,
                    ...updated_query,
                }),
            );

            const result = await QueryUpdater().updateQuery(current_query, updated_query);
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }

            expect(putJSON).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/crosstracker_query/${current_query.id}`,
                updated_query,
            );
            expect(result.value.id).toBe(current_query.id);
            expect(result.value.tql_query).toBe(updated_query.tql_query);
            expect(result.value.title).toBe(updated_query.title);
            expect(result.value.description).toBe(updated_query.description);
            expect(result.value.is_default).toBe(updated_query.is_default);
        });
    });
});
