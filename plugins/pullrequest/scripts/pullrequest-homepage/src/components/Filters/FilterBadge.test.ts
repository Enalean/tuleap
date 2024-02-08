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
import { shallowMount } from "@vue/test-utils";
import { ref } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import FilterBadge from "./FilterBadge.vue";
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";
import type { PullRequestsListFilter } from "./PullRequestsListFilter";
import { ListFiltersStore } from "./ListFiltersStore";
import type { StoreListFilters } from "./ListFiltersStore";
import { LabelFilterBuilder } from "./Labels/LabelFilter";
import type { BuildLabelFilter } from "./Labels/LabelFilter";
import { ProjectLabelStub } from "../../../tests/stubs/ProjectLabelStub";
import { AuthorFilterBuilder } from "./Author/AuthorFilter";
import type { BuildAuthorFilter } from "./Author/AuthorFilter";
import { UserStub } from "../../../tests/stubs/UserStub";
import { GettextStub } from "../../../tests/stubs/GettextStub";

describe("FilterBadge", () => {
    let filter: PullRequestsListFilter,
        filters_store: StoreListFilters,
        label_filter_builder: BuildLabelFilter,
        author_filter_builder: BuildAuthorFilter;

    beforeEach(() => {
        label_filter_builder = LabelFilterBuilder(GettextStub);
        author_filter_builder = AuthorFilterBuilder(GettextStub);

        filter = label_filter_builder.fromLabel(
            ProjectLabelStub.regulardWithIdAndLabel(1, "Emergency"),
        );

        filters_store = ListFiltersStore(ref([]));
    });

    const getWrapper = (): VueWrapper => {
        filters_store.storeFilter(filter);

        return shallowMount(FilterBadge, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                filter,
                filters_store,
            },
        });
    };

    it(`Given that the filter is a label filter
        When the label is outlined
        Then the badge should be outlined too`, () => {
        filter = label_filter_builder.fromLabel(
            ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency"),
        );

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=list-filter-badge]").classes()).toStrictEqual([
            "pull-request-homepage-filter-badge",
            "tlp-badge-outline",
            `tlp-badge-${filter.value.color}`,
        ]);
    });

    it(`Given that the filter is a label filter
        When the label is NOT outlined
        Then the badge should NOT be outlined`, () => {
        filter = label_filter_builder.fromLabel(
            ProjectLabelStub.regulardWithIdAndLabel(1, "Emergency"),
        );

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=list-filter-badge]").classes()).toStrictEqual([
            "pull-request-homepage-filter-badge",
            `tlp-badge-${filter.value.color}`,
        ]);
    });

    it(`Given that the filter is NOT a label filter
        Then the badge should be primary and outlined`, () => {
        filter = author_filter_builder.fromAuthor(UserStub.withIdAndName(102, "John Doe (jdoe)"));

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=list-filter-badge]").classes()).toStrictEqual([
            "pull-request-homepage-filter-badge",
            "tlp-badge-outline",
            "tlp-badge-primary",
        ]);
    });

    it("When the user clicks on the cross button in the filter badge, then it should remove it from the store", async () => {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=list-filter-badge-delete-button]").trigger("click");
        expect(filters_store.getFilters().value).toHaveLength(0);
    });
});
