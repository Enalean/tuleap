/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, beforeEach, it, expect } from "vitest";
import { buildQueryFromFilters } from "./get-pull-requests-query-builder";
import { AuthorFilterBuilder } from "../components/Filters/Author/AuthorFilter";
import { UserStub } from "../../tests/stubs/UserStub";

const $gettext = (string: string): string => string;

describe("get-pull-requests-query-builder", () => {
    let are_closed_pull_requests_shown: boolean;

    beforeEach(() => {
        are_closed_pull_requests_shown = true;
    });

    describe("Author filter", () => {
        it("Given a filter on author, then it should return a proper query string", () => {
            const user_id = 102;
            const query = buildQueryFromFilters(
                [
                    AuthorFilterBuilder($gettext).fromAuthor(
                        UserStub.withIdAndName(user_id, "John Doe (jdoe)"),
                    ),
                ],
                are_closed_pull_requests_shown,
            );

            expect(query).toContain(JSON.stringify({ authors: [{ id: user_id }] }));
        });
    });

    describe("Closed pull-requests filter", () => {
        it('When closed pull-requests are shown, then it should NOT set { status: "open" } in the query', () => {
            are_closed_pull_requests_shown = true;
            const query = buildQueryFromFilters([], are_closed_pull_requests_shown);

            expect(query).toStrictEqual(JSON.stringify({}));
        });

        it('When closed pull-requests are hidden, then it should set { status: "open" } in the query', () => {
            are_closed_pull_requests_shown = false;
            const query = buildQueryFromFilters([], are_closed_pull_requests_shown);

            expect(query).toContain(JSON.stringify({ status: "open" }));
        });
    });
});
