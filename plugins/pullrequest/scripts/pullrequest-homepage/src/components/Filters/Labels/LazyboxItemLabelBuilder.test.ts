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

import { ref } from "vue";
import { describe, it, expect, beforeEach } from "vitest";
import { ListFiltersStore } from "../ListFiltersStore";
import type { StoreListFilters } from "../ListFiltersStore";
import { ProjectLabelStub } from "../../../../tests/stubs/ProjectLabelStub";
import { LabelFilterBuilder } from "./LabelFilter";
import type { BuildLabelFilter } from "./LabelFilter";
import { GettextStub } from "../../../../tests/stubs/GettextStub";
import { LazyboxItemLabelBuilder } from "./LazyboxItemLabelBuilder";

describe("LazyboxItemLabelBuilder", () => {
    let filters_store: StoreListFilters, build_label_filter: BuildLabelFilter;

    beforeEach(() => {
        filters_store = ListFiltersStore(ref([]));
        build_label_filter = LabelFilterBuilder(GettextStub);
    });

    describe("fromLabel()", () => {
        it("Given a label, When a filter exists on this label, then it should return a disabled LazyboxItem", () => {
            const label = ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency");
            filters_store.storeFilter(build_label_filter.fromLabel(label));

            const item = LazyboxItemLabelBuilder(filters_store).fromLabel(label);

            expect(item.value).toStrictEqual(label);
            expect(item.is_disabled).toStrictEqual(true);
        });

        it("Given a label, When no filter exists on this label, then it should return an enabled LazyboxItem", () => {
            const label = ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency");
            const item = LazyboxItemLabelBuilder(filters_store).fromLabel(label);

            expect(item.value).toStrictEqual(label);
            expect(item.is_disabled).toStrictEqual(false);
        });
    });

    describe("fromLazyboxItem()", () => {
        it("Given a LazyboxItem containing a label, When a filter exists on this label, then it should return a disabled LazyboxItem", () => {
            const label = ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency");
            filters_store.storeFilter(build_label_filter.fromLabel(label));

            const item = LazyboxItemLabelBuilder(filters_store).fromLazyboxItem({
                value: label,
                is_disabled: false,
            });

            expect(item.value).toStrictEqual(label);
            expect(item.is_disabled).toStrictEqual(true);
        });

        it("Given a LazyboxItem containing a label, When no filter exists on this label, then it should return an enabled LazyboxItem", () => {
            const label = ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency");
            const item = LazyboxItemLabelBuilder(filters_store).fromLazyboxItem({
                value: label,
                is_disabled: false,
            });

            expect(item.value).toStrictEqual(label);
            expect(item.is_disabled).toStrictEqual(false);
        });
    });
});
