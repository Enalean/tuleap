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
import localVue from "../../support/local-vue.js";
import NewComparisonModal from "./NewComparisonModal.vue";
import { createList } from "../../support/factories";

describe("NewComparisonModal", () => {
    let wrapper;
    let baselines;

    beforeEach(() => {
        baselines = createList("baseline", 2);
        wrapper = shallowMount(NewComparisonModal, {
            localVue,
            propsData: { baselines }
        });
    });

    describe("reload()", () => {
        beforeEach(() => {
            wrapper.setData({
                base_baseline: baselines[0],
                baseline_to_compare: baselines[1]
            });
            wrapper.vm.reload();
        });

        it("resets inputs", () => {
            expect(wrapper.vm.base_baseline).toBeNull();
            expect(wrapper.vm.baseline_to_compare).toBeNull();
        });
    });
});
