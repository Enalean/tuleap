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

import { shallowMount } from "@vue/test-utils";
import SearchResultTable from "./SearchResultTable.vue";
import localVue from "../../../helpers/local-vue";
import TableBodySkeleton from "./TableBodySkeleton.vue";
import TableBodyEmpty from "./TableBodyEmpty.vue";

describe("SearchResultTable", () => {
    it("should display skeleton while loading", () => {
        const wrapper = shallowMount(SearchResultTable, {
            localVue,
            propsData: {
                is_loading: true,
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(true);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(false);
    });

    it("should display empty state when no results to display", () => {
        const wrapper = shallowMount(SearchResultTable, {
            localVue,
            propsData: {
                is_loading: false,
            },
        });

        expect(wrapper.findComponent(TableBodySkeleton).exists()).toBe(false);
        expect(wrapper.findComponent(TableBodyEmpty).exists()).toBe(true);
    });
});
