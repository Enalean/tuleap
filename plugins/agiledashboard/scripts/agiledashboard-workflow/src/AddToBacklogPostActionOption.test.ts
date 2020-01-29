/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { createLocalVueForAgileDashboardWorkflow } from "./helpers/local-vue-for-test";
import { shallowMount, Wrapper } from "@vue/test-utils";
import AddToBacklogPostActionOption from "./AddToBacklogPostActionOption.vue";

describe("AddToBacklogPostAction", () => {
    let wrapper: Wrapper<AddToBacklogPostActionOption>;
    beforeEach(async () => {
        wrapper = shallowMount(AddToBacklogPostActionOption, {
            localVue: await createLocalVueForAgileDashboardWorkflow()
        });
    });
    it("Spawns the component", () => {
        expect(wrapper.contains("[data-test=add-to-backlog]")).toBe(true);
    });
});
