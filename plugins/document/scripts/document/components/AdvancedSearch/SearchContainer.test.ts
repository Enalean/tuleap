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

import { createLocalVue, shallowMount } from "@vue/test-utils";
import SearchContainer from "./SearchContainer.vue";
import SearchResultTable from "./SearchResultTable.vue";
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import type { AdvancedSearchParams } from "../../type";
import VueRouter from "vue-router";

describe("SearchContainer", () => {
    it("should not display the table results if the query is empty", () => {
        const wrapper = shallowMount(SearchContainer, {
            propsData: {
                query: "",
            },
        });

        expect(wrapper.findComponent(SearchResultTable).exists()).toBe(false);
    });

    it("should route to a new search if user changes criteria", () => {
        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: "",
            },
            mocks: {
                $route: {
                    query: { q: "" },
                },
                $router: router,
            },
        });

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        const params: AdvancedSearchParams = {
            query: "Lorem ipsum",
        };
        criteria.vm.$emit("advanced-search", params);

        expect(router.push).toHaveBeenCalledWith({
            name: "search",
            query: {
                q: "Lorem ipsum",
            },
        });
    });

    it("should not route to a new search if user didn't change the criteria", () => {
        const router = new VueRouter();
        jest.spyOn(router, "push").mockImplementation();

        const wrapper = shallowMount(SearchContainer, {
            localVue: createLocalVue().use(VueRouter),
            propsData: {
                query: "Lorem ipsum",
            },
            mocks: {
                $route: {
                    query: { q: "Lorem ipsum" },
                },
                $router: router,
            },
        });

        const criteria = wrapper.findComponent(SearchCriteriaPanel);
        const params: AdvancedSearchParams = {
            query: "Lorem ipsum",
        };
        criteria.vm.$emit("advanced-search", params);

        expect(router.push).not.toHaveBeenCalled();
    });
});
