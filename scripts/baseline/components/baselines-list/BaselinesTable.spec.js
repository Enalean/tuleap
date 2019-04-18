/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import BaselinesTable from "./BaselinesTable.vue";
import BaselinesTableBodySkeleton from "./BaselinesTableBodySkeleton.vue";
import BaselinesTableBodyCells from "./BaselinesTableBodyCells.vue";
import { createList } from "../../support/factories";

describe("BaselinesTable", () => {
    const empty_baseline_selector = '[data-test-type="empty-baseline"]';
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(BaselinesTable, {
            localVue,
            propsData: {
                baselines: [],
                is_loading: false
            }
        });
    });

    describe("when is loading baselines", () => {
        beforeEach(async () => {
            wrapper.setProps({ baselines: null, is_loading: true });
            await Vue.nextTick();
        });

        it("does not show any baseline", () => {
            expect(wrapper.contains(BaselinesTableBodyCells)).toBeFalsy();
        });

        it("shows body table skeleton", () => {
            expect(wrapper.contains(BaselinesTableBodySkeleton)).toBeTruthy();
        });

        it("does not show a message that specifies an empty state", () => {
            expect(wrapper.contains(empty_baseline_selector)).toBeFalsy();
        });
    });

    describe("when many baselines", () => {
        beforeEach(async () => {
            wrapper.setProps({
                baselines: createList("baseline", 3)
            });
            await Vue.nextTick();
        });

        it("shows baselines", () => {
            expect(wrapper.contains(BaselinesTableBodyCells)).toBeTruthy();
        });

        it("does not show body table skeleton", () => {
            expect(wrapper.contains(BaselinesTableBodySkeleton)).toBeFalsy();
        });

        it("does not show a message that specifies an empty state", () => {
            expect(wrapper.contains(empty_baseline_selector)).toBeFalsy();
        });
    });

    describe("when no baseline", () => {
        it("does not show baselines", () => {
            expect(wrapper.contains(BaselinesTableBodyCells)).toBeFalsy();
        });

        it("does not show body table skeleton", () => {
            expect(wrapper.contains(BaselinesTableBodySkeleton)).toBeFalsy();
        });

        it("shows a message that specifies an empty state", () => {
            expect(wrapper.contains(empty_baseline_selector)).toBeTruthy();
        });
    });
});
