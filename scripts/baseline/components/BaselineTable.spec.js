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
 */

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";
import localVue from "../support/local-vue.js";
import BaselineTable from "./BaselineTable.vue";
import BaselineTableBodySkeleton from "./BaselineTableBodySkeleton.vue";
import BaselineTableBodyCells from "./BaselineTableBodyCells.vue";

describe("BaselineTable", () => {
    const empty_baseline_selector = '[data-test-type="empty-baseline"]';
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(BaselineTable, {
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
            expect(wrapper.contains(BaselineTableBodyCells)).toBeFalsy();
        });

        it("shows body table skeleton", () => {
            expect(wrapper.contains(BaselineTableBodySkeleton)).toBeTruthy();
        });

        it("does not show a message that specifies an empty state", () => {
            expect(wrapper.contains(empty_baseline_selector)).toBeFalsy();
        });
    });

    describe("when many baselines", () => {
        beforeEach(async () => {
            wrapper.setProps({
                baselines: [
                    {
                        id: 1,
                        name: "Baseline V1",
                        snapshot_date: "10/02/2019",
                        author_id: 1
                    },
                    {
                        id: 2,
                        name: "Baseline V2",
                        snapshot_date: "11/02/2019",
                        author_id: 2
                    },
                    {
                        id: 3,
                        name: "Baseline V3",
                        snapshot_date: "12/02/2019",
                        author_id: 3
                    }
                ]
            });
            await Vue.nextTick();
        });

        it("shows baselines", () => {
            expect(wrapper.contains(BaselineTableBodyCells)).toBeTruthy();
        });

        it("does not show body table skeleton", () => {
            expect(wrapper.contains(BaselineTableBodySkeleton)).toBeFalsy();
        });

        it("does not show a message that specifies an empty state", () => {
            expect(wrapper.contains(empty_baseline_selector)).toBeFalsy();
        });
    });

    describe("when no baseline", () => {
        it("does not show baselines", () => {
            expect(wrapper.contains(BaselineTableBodyCells)).toBeFalsy();
        });

        it("does not show body table skeleton", () => {
            expect(wrapper.contains(BaselineTableBodySkeleton)).toBeFalsy();
        });

        it("shows a message that specifies an empty state", () => {
            expect(wrapper.contains(empty_baseline_selector)).toBeTruthy();
        });
    });
});
