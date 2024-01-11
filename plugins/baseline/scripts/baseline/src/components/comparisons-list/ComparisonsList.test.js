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
import { createLocalVueForTests } from "../../support/local-vue.ts";
import ComparisonsList from "./ComparisonsList.vue";
import ComparisonSkeleton from "./ComparisonSkeleton.vue";
import ComparisonItem from "./ComparisonItem.vue";
import { createStoreMock } from "../../support/store-wrapper.test-helper";
import store_options from "../../store/store_options";

describe("ComparisonsList", () => {
    const empty_comparison_selector = '[data-test-type="empty-comparison"]';
    let $store, wrapper;

    beforeEach(async () => {
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
            localVue: await createLocalVueForTests(),
            mocks: { $store },
        });
    });

    describe("when comparisons are loading", () => {
        beforeEach(() => ($store.state.comparisons.is_loading = true));

        it("does not show any comparison", () => {
            expect(wrapper.findComponent(ComparisonItem).exists()).toBeFalsy();
        });

        it("shows body table skeleton", () => {
            expect(wrapper.findComponent(ComparisonSkeleton).exists()).toBeTruthy();
        });

        it("does not show a message that specifies an empty state", () => {
            expect(wrapper.find(empty_comparison_selector).exists()).toBeFalsy();
        });
    });

    describe("when comparisons are loaded", () => {
        beforeEach(() => ($store.state.comparisons.is_loading = false));

        describe("with many comparisons", () => {
            beforeEach(() => {
                $store.state.comparisons.comparisons = [
                    {
                        base_baseline_id: 1,
                        compared_to_baseline_id: 2,
                    },
                    {
                        base_baseline_id: 1,
                        compared_to_baseline_id: 2,
                    },
                    {
                        base_baseline_id: 1,
                        compared_to_baseline_id: 2,
                    },
                ];
                $store.getters["comparisons/are_some_available"] = true;
            });

            it("shows comparisons", () => {
                expect(wrapper.findComponent(ComparisonItem).exists()).toBeTruthy();
            });

            it("does not show body table skeleton", () => {
                expect(wrapper.findComponent(ComparisonSkeleton).exists()).toBeFalsy();
            });

            it("does not show a message that specifies an empty state", () => {
                expect(wrapper.find(empty_comparison_selector).exists()).toBeFalsy();
            });
        });

        describe("without any comparison", () => {
            beforeEach(() => {
                $store.state.comparisons.comparisons = [];
                $store.getters["comparisons/are_some_available"] = false;
            });

            it("does not show comparisons", () => {
                expect(wrapper.findComponent(ComparisonItem).exists()).toBeFalsy();
            });

            it("does not show body table skeleton", () => {
                expect(wrapper.findComponent(ComparisonSkeleton).exists()).toBeFalsy();
            });

            it("shows a message that specifies an empty state", () => {
                expect(wrapper.find(empty_comparison_selector).exists()).toBeTruthy();
            });
        });
    });
});
