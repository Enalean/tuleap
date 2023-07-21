/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import CellDisallowsDropOverlay from "./CellDisallowsDropOverlay.vue";
import { createTaskboardLocalVue } from "../../../../../helpers/local-vue-for-test";

describe("CellDisallowsDropOverlay", () => {
    it("displays div with an icon and an error message", async () => {
        const wrapper = shallowMount(CellDisallowsDropOverlay, {
            propsData: {
                isColumnCollapsed: false,
            },
            localVue: await createTaskboardLocalVue(),
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Does not render the error message when the column is collapsed", async () => {
        const wrapper = shallowMount(CellDisallowsDropOverlay, {
            propsData: {
                isColumnCollapsed: true,
            },
            localVue: await createTaskboardLocalVue(),
        });

        expect(wrapper.find("[data-test=overlay-error-message]").exists()).toBe(false);
    });
});
