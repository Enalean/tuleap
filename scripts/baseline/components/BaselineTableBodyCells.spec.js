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

import { shallowMount } from "@vue/test-utils";
import localVue from "../support/local-vue.js";
import BaselineTableBodyCells from "./BaselineTableBodyCells.vue";
import { createList } from "../support/factories";

describe("BaselineTableBodyCells", () => {
    const baseline_selector = '[data-test-type="baseline"]';
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(BaselineTableBodyCells, {
            localVue,
            propsData: {
                baselines: createList("baseline", 3, "presented")
            }
        });
    });

    it("shows as many baselines as given", () => {
        let baselines = wrapper.findAll(baseline_selector);
        expect(baselines.length).toBe(3);
    });
});
