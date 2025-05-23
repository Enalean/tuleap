/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
import * as fetch_result from "@tuleap/fetch-result";
import { deleteQuery, getQueries } from "./rest-querier";

describe("rest-querier", () => {
    describe("getQueries()", () => {
        it(`will query the REST API and return the queries`, async () => {
            const widget = {
                queries: [
                    {
                        id: "0194d59b-f37b-73e1-a553-cf143a3c1203",
                        tql_query: '@title = "bla"',
                        title: "TQL title",
                        description: '@title = "bla"',
                    },
                ],
            };
            const getJSON = vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(widget));
            const widget_id = 16;

            const result = await getQueries(widget_id);

            expect(getJSON).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/crosstracker_widget/${widget_id}`,
            );
            if (!result.isOk()) {
                throw Error("Expected an ok");
            }
            expect(result.value[0].tql_query).toBe(widget.queries[0].tql_query);
            expect(result.value[0].title).toBe(widget.queries[0].title);
            expect(result.value[0].description).toBe(widget.queries[0].description);
        });
    });

    describe("deleteQuery()", () => {
        it(`will query the REST API and will return nothing`, async () => {
            const query = {
                id: "0194d59b-f37b-73e1-a553-cf143a3c1203",
                tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                title: "My query",
                description: "My description",
                is_default: false,
            };
            const del = vi.spyOn(fetch_result, "del").mockReturnValue(okAsync({} as Response));

            const result = await deleteQuery(query);

            expect(del).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/crosstracker_query/${query.id}`,
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toBe(null);
        });
    });
});
