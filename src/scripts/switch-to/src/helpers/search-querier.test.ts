/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import * as fetch_result from "@tuleap/fetch-result";
import { query } from "./search-querier";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { ItemDefinition } from "../type";

describe("search-querier", () => {
    describe("query", () => {
        it("should propagate the API error", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockReturnValue(errAsync(Fault.fromMessage("Something went wrong")));

            const result = await query("/search", "keywords");
            expect(result.isErr()).toBe(true);
        });

        it("should return the results", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockReturnValue(
                okAsync({
                    json: () =>
                        Promise.resolve([
                            { title: "toto", html_url: "/toto" },
                            { title: "titi", html_url: "/titi" },
                        ] as ItemDefinition[]),
                } as unknown as Response)
            );

            const result = await query("/search", "keywords");
            expect(result.unwrapOr({})).toStrictEqual({
                "/toto": { title: "toto", html_url: "/toto" },
                "/titi": { title: "titi", html_url: "/titi" },
            });
        });

        it("should deduplicate the results", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockReturnValue(
                okAsync({
                    json: () =>
                        Promise.resolve([
                            { title: "toto", html_url: "/toto" },
                            { title: "toto", html_url: "/toto" },
                        ] as ItemDefinition[]),
                } as unknown as Response)
            );

            const result = await query("/search", "keywords");
            expect(result.unwrapOr({})).toStrictEqual({
                "/toto": { title: "toto", html_url: "/toto" },
            });
        });
    });
});
