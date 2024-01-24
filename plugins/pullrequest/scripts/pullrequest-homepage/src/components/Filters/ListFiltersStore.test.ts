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

import { describe, it, expect, beforeEach } from "vitest";
import { ref } from "vue";
import { AuthorFilterStub } from "../../../tests/stubs/AuthorFilterStub";
import { ListFiltersStore } from "./ListFiltersStore";
import type { StoreListFilters } from "./ListFiltersStore";
import { TYPE_FILTER_AUTHOR } from "./Author/AuthorFilter";
import { UserStub } from "../../../tests/stubs/UserStub";

describe("ListFiltersStore", () => {
    let store: StoreListFilters;

    beforeEach(() => {
        store = ListFiltersStore(ref([]));
    });

    describe("storeFilter()", () => {
        it("Should store the given filter", () => {
            const filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe"));

            store.storeFilter(filter);

            expect(store.getFilters().value).toHaveLength(1);
            expect(store.getFilters().value).toStrictEqual([filter]);
        });

        it("When a filter with the same type already exist and the filter only allow one value, Then it should replace the existing one with the new one", () => {
            const old_filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe"));
            const new_filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(2, "Jane Doe"));

            store.storeFilter(old_filter);
            store.storeFilter(new_filter);

            expect(store.getFilters().value).toHaveLength(1);
            expect(store.getFilters().value).toStrictEqual([new_filter]);
        });
    });

    describe("deleteFilter()", () => {
        it("Given a filter, then it should remove it from the store", () => {
            const filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe"));

            store.storeFilter(filter);
            store.deleteFilter(filter);

            expect(store.getFilters().value).toHaveLength(0);
        });
    });

    describe("clearAllFilters()", () => {
        it("should remove all the filters from the store", () => {
            const filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe"));

            store.storeFilter(filter);
            expect(store.getFilters().value).toHaveLength(1);

            store.clearAllFilters();
            expect(store.getFilters().value).toHaveLength(0);
        });
    });

    describe("hasAFilterWithType()", () => {
        it("should return true when a filter of the given type already exists in the store", () => {
            const filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe"));
            store.storeFilter(filter);

            expect(store.hasAFilterWithType(TYPE_FILTER_AUTHOR)).toBe(true);
        });

        it("should return false when no filter of the given type already exists in the store", () => {
            store.clearAllFilters();

            expect(store.hasAFilterWithType(TYPE_FILTER_AUTHOR)).toBe(false);
        });
    });
});
