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
import FileInput from "./FileInput.vue";
import { createNewThreadLocalVue } from "../helpers/local-vue-for-test";
import Vue from "vue";

describe("FileInput", () => {
    it("Displays the input field to enter a file", async () => {
        const wrapper = shallowMount(FileInput, {
            localVue: await createNewThreadLocalVue(),
            propsData: {
                index: 123,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Asks to parent component to remove the file if user clicks on delete button", async () => {
        const wrapper = shallowMount(FileInput, {
            localVue: await createNewThreadLocalVue(),
            propsData: {
                index: 123,
            },
        });

        const button = wrapper.find('[data-test="delete-file"]');
        button.trigger("click");

        await Vue.nextTick();

        expect(wrapper.emitted().removeFile).toBeTruthy();
    });
});
