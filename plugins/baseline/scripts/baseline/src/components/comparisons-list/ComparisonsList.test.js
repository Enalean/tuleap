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
import ComparisonsList from "./ComparisonsList.vue";
import ComparisonSkeleton from "./ComparisonSkeleton.vue";
import ComparisonItem from "./ComparisonItem.vue";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";

describe("ComparisonsList", () => {
    const empty_comparison_selector = '[data-test-type="empty-comparison"]';

    function createWrapper(is_loading, comparisons, are_some_available) {
        return shallowMount(ComparisonsList, {
            props: {
                project_id: 102,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        comparisons: {
                            namespaced: true,
                            getters: {
                                are_some_available: () => are_some_available,
                            },
                            state: {
                                is_loading,
                                comparisons,
                            },
                            actions: {
                                load: jest.fn(),
                            },
                        },
                    },
                }),
            },
        });
    }

    describe("when comparisons are loading", () => {
        it("does not show any comparison", () => {
            const wrapper = createWrapper(true, [], false);
            expect(wrapper.findComponent(ComparisonItem).exists()).toBeFalsy();
        });

        it("shows body table skeleton", () => {
            const wrapper = createWrapper(true, [], false);
            expect(wrapper.findComponent(ComparisonSkeleton).exists()).toBeTruthy();
        });

        it("does not show a message that specifies an empty state", () => {
            const wrapper = createWrapper(true, [], false);
            expect(wrapper.find(empty_comparison_selector).exists()).toBeFalsy();
        });
    });

    describe("when comparisons are loaded", () => {
        describe("with many comparisons", () => {
            it(`shows comparisons, does not show body table skeleton
                and does not show a message that specifies an empty state`, () => {
                const comparisons = [
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

                const wrapper = createWrapper(false, comparisons, true);

                expect(wrapper.findComponent(ComparisonItem).exists()).toBeTruthy();
                expect(wrapper.findComponent(ComparisonSkeleton).exists()).toBeFalsy();
                expect(wrapper.find(empty_comparison_selector).exists()).toBeFalsy();
            });
        });

        describe("without any comparison", () => {
            it(`does not show comparisons, does not show body table skeleton
                and shows a message that specifies an empty state`, () => {
                const wrapper = createWrapper(false, [], false);

                expect(wrapper.findComponent(ComparisonItem).exists()).toBeFalsy();
                expect(wrapper.findComponent(ComparisonSkeleton).exists()).toBeFalsy();
                expect(wrapper.find(empty_comparison_selector).exists()).toBeTruthy();
            });
        });
    });
});
