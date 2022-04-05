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
import App from "./App.vue";
import { createNewThreadLocalVue } from "../helpers/local-vue-for-test";
import CcInput from "./CcInput.vue";
import Vue from "vue";
import FileInput from "./FileInput.vue";

describe("App", () => {
    it("Displays two buttons to add Cc or file attachment", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createNewThreadLocalVue(),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Adds CcInput components when we click on [Add Cc] button", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createNewThreadLocalVue(),
        });

        const button = wrapper.find("[data-test=add-cc]");
        button.trigger("click");
        await Vue.nextTick();
        button.trigger("click");
        await Vue.nextTick();

        expect(wrapper.findAllComponents(CcInput)).toHaveLength(2);
        expect(wrapper.findAllComponents(FileInput)).toHaveLength(0);
    });

    it("Adds FileInput components when we click on [Add attachment] button", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createNewThreadLocalVue(),
        });

        const button = wrapper.find("[data-test=add-file]");
        button.trigger("click");
        await Vue.nextTick();
        button.trigger("click");
        await Vue.nextTick();

        expect(wrapper.findAllComponents(CcInput)).toHaveLength(0);
        expect(wrapper.findAllComponents(FileInput)).toHaveLength(2);
    });
});
