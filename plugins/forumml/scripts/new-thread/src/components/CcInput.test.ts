/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import CcInput from "./CcInput.vue";
import Vue from "vue";

describe("CcInput", () => {
    it("Displays the input field to enter a cc", () => {
        const wrapper = shallowMount(CcInput, {
            propsData: {
                cc: "toto@example.com",
                index: 123,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Asks to parent component to remove the cc if user clicks on delete button", async () => {
        const wrapper = shallowMount(CcInput, {
            propsData: {
                cc: "toto@example.com",
                index: 123,
            },
        });

        const button = wrapper.find('[data-test="delete-cc"]');
        button.trigger("click");

        await Vue.nextTick();

        expect(wrapper.emitted().removeCc).toBeTruthy();
    });
});
