/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import ComparisonsList from "./ComparisonsList.vue";
import ComparisonSkeleton from "./ComparisonSkeleton.vue";
import Comparison from "./Comparison.vue";
import { createList } from "../../support/factories";
import { createStoreMock } from "../../support/store-wrapper.test-helper";
import store_options from "../../store/store_options";

describe("ComparisonsList", () => {
    const empty_comparison_selector = '[data-test-type="empty-comparison"]';
    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock({
            ...store_options,
            getters: {
                "comparisons/are_some_available": false,
            },
        });

        wrapper = shallowMount(ComparisonsList, {
            propsData: {
                project_id: 102,
            },
            localVue,
            mocks: { $store },
        });
    });

    describe("when comparisons are loading", () => {
        beforeEach(() => ($store.state.comparisons.is_loading = true));

        it("does not show any comparison", () => {
            expect(wrapper.contains(Comparison)).toBeFalsy();
        });

        it("shows body table skeleton", () => {
            expect(wrapper.contains(ComparisonSkeleton)).toBeTruthy();
        });

        it("does not show a message that specifies an empty state", () => {
            expect(wrapper.contains(empty_comparison_selector)).toBeFalsy();
        });
    });

    describe("when comparisons are loaded", () => {
        beforeEach(() => ($store.state.comparisons.is_loading = false));

        describe("with many comparisons", () => {
            beforeEach(() => {
                $store.state.comparisons.comparisons = createList("comparison", 3);
                $store.getters["comparisons/are_some_available"] = true;
            });

            it("shows comparisons", () => {
                expect(wrapper.contains(Comparison)).toBeTruthy();
            });

            it("does not show body table skeleton", () => {
                expect(wrapper.contains(ComparisonSkeleton)).toBeFalsy();
            });

            it("does not show a message that specifies an empty state", () => {
                expect(wrapper.contains(empty_comparison_selector)).toBeFalsy();
            });
        });

        describe("without any comparison", () => {
            beforeEach(() => {
                $store.state.comparisons.comparisons = [];
                $store.getters["comparisons/are_some_available"] = false;
            });

            it("does not show comparisons", () => {
                expect(wrapper.contains(Comparison)).toBeFalsy();
            });

            it("does not show body table skeleton", () => {
                expect(wrapper.contains(ComparisonSkeleton)).toBeFalsy();
            });

            it("shows a message that specifies an empty state", () => {
                expect(wrapper.contains(empty_comparison_selector)).toBeTruthy();
            });
        });
    });
});
