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

import { describe, it, expect, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { NewQueryCreator } from "./NewQueryCreator";

describe("NewQueryCreator", () => {
    describe("postNewQuery()", () => {
        const query_id = "0194d59b-f37b-73e1-a553-cf143a3c1203";

        const query_to_post = {
            widget_id: 3,
            tql_query: "SELECT  @id, @project.name FROM @project = MY_PROJECTS() WHERE @id > 2",
            title: "My TQL query",
            description: "My description",
            is_default: false,
        };

        it("should send the query to the REST API and return the just created query", async () => {
            const postJSON = vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({
                    id: query_id,
                    ...query_to_post,
                }),
            );

            const result = await NewQueryCreator().postNewQuery(query_to_post);
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }

            expect(postJSON).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/crosstracker_query`,
                query_to_post,
            );
            expect(result.value.id).toBe(query_id);
            expect(result.value.tql_query).toBe(query_to_post.tql_query);
            expect(result.value.title).toBe(query_to_post.title);
            expect(result.value.description).toBe(query_to_post.description);
            expect(result.value.is_default).toBe(query_to_post.is_default);
        });
    });
});
