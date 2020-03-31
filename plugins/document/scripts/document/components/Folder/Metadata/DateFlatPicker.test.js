/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import localVue from "../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import DateFlatPicker from "./DateFlatPicker.vue";

describe("DateFlatPicker", () => {
    let factory;
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(DateFlatPicker, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it(`User can choose a date value`, async () => {
        const wrapper = factory({ id: "input-date", value: "2019-06-30" });

        wrapper.vm.onDatePickerChange();
        await wrapper.vm.$nextTick().then(() => {});

        expect(wrapper.emitted().input).toEqual([["2019-06-30"]]);
    });

    it(`User can reset date value to empty one`, async () => {
        const wrapper = factory({ id: "input-date", value: "" });

        wrapper.vm.onDatePickerChange();
        await wrapper.vm.$nextTick().then(() => {});

        expect(wrapper.emitted().input).toEqual([[""]]);
    });

    it(`User can set a date value manually`, () => {
        const wrapper = factory({ id: "input-date", value: "" });

        const input = wrapper.get("input");
        input.element.value = "2019-06-30";
        input.trigger("input");

        expect(wrapper.emitted().input).toBeTruthy();
        expect(wrapper.emitted().input[0]).toEqual(["2019-06-30"]);
    });
});
