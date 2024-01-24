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

import { describe, it, expect, vi } from "vitest";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { UserStub } from "../../../../tests/stubs/UserStub";
import * as rest_querier from "../../../api/tuleap-rest-querier";
import { AuthorsLoader } from "./AuthorsLoader";

const authors: User[] = [
    UserStub.withIdAndName(101, "Joe l'asticot (jolasti)"),
    UserStub.withIdAndName(102, "John Doe (jdoe)"),
    UserStub.withIdAndName(5, "Johann Zarco (jz5)"),
];

const repository_id = 2;

describe("AuthorsLoader", () => {
    it("should load the authors and return a collection of LazyboxItem", async () => {
        vi.spyOn(rest_querier, "fetchPullRequestsAuthors").mockReturnValue(okAsync(authors));

        const loadItems = AuthorsLoader(() => {
            // Do nothing
        }, repository_id);
        const items = await loadItems();

        expect(rest_querier.fetchPullRequestsAuthors).toHaveBeenCalledWith(repository_id);
        expect(items).toHaveLength(authors.length);
        expect(items).toStrictEqual([
            { value: authors[0], is_disabled: false },
            { value: authors[1], is_disabled: false },
            { value: authors[2], is_disabled: false },
        ]);
    });

    it("When an error occurres, Then it should call the on_error_callback and return an empty array", async () => {
        const tuleap_api_error = Fault.fromMessage("Oops!");
        const on_error_callback = vi.fn();

        vi.spyOn(rest_querier, "fetchPullRequestsAuthors").mockReturnValue(
            errAsync(tuleap_api_error),
        );

        const loadItems = AuthorsLoader(on_error_callback, repository_id);
        const items = await loadItems();

        expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_error);
        expect(items).toHaveLength(0);
    });
});
