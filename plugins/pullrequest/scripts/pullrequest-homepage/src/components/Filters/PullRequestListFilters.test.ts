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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { ref } from "vue";
import PullRequestListFilters from "./PullRequestListFilters.vue";
import type { DisplayErrorCallback } from "../../injection-symbols";
import { StubInjectionSymbols } from "../../../tests/injection-symbols-stub";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";
import { ListFiltersStore } from "./ListFiltersStore";
import type { StoreListFilters } from "./ListFiltersStore";
import { AuthorFilterStub } from "../../../tests/stubs/AuthorFilterStub";
import { UserStub } from "../../../tests/stubs/UserStub";

describe("PullRequestListFilters", () => {
    let tuleap_api_error_callback: DisplayErrorCallback, store_filters: StoreListFilters;

    beforeEach(() => {
        tuleap_api_error_callback = vi.fn();
        store_filters = ListFiltersStore(ref([]));
    });

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withTuleapApiErrorCallback(tuleap_api_error_callback),
        );

        return shallowMount(PullRequestListFilters, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                filters_store: store_filters,
            },
        });
    };

    it("When a filter is added to the store, then it should display it", async () => {
        const wrapper = getWrapper();
        const filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe"));

        store_filters.storeFilter(filter);

        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=list-filter-badge]").exists()).toBe(true);
    });

    it("When the user clicks on the cross button in the filter badge, then it should delete it", async () => {
        const wrapper = getWrapper();
        const filter = AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe"));

        store_filters.storeFilter(filter);
        await wrapper.vm.$nextTick();

        await wrapper.find("[data-test=list-filter-badge-delete-button]").trigger("click");
        expect(wrapper.find("[data-test=list-filter-badge]").exists()).toBe(false);
    });

    it("The [Clear filters] button should be deactivated when there is filter yet", async () => {
        const wrapper = getWrapper();
        expect(
            wrapper.find("[data-test=clear-all-list-filters]").attributes("disabled"),
        ).toBeDefined();

        store_filters.storeFilter(
            AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe")),
        );
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=clear-all-list-filters]").attributes("disabled"),
        ).not.toBeDefined();
    });

    it("When the user clicks on the [Clear filters] button, then it should delete all the filters", async () => {
        const wrapper = getWrapper();
        store_filters.storeFilter(
            AuthorFilterStub.fromAuthor(UserStub.withIdAndName(1, "John Doe")),
        );

        await wrapper.vm.$nextTick();
        expect(wrapper.findAll("[data-test=list-filter-badge]")).toHaveLength(1);

        await wrapper.find("[data-test=clear-all-list-filters]").trigger("click");
        expect(wrapper.findAll("[data-test=list-filter-badge]")).toHaveLength(0);
    });
});
