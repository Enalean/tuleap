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
import FloatInput from "./FloatInput.vue";
import { createLocalVueForTests } from "../../../support/local-vue.js";

describe("FloatInput", () => {
    let wrapper;

    beforeEach(async () => {
        wrapper = shallowMount(FloatInput, {
            propsData: { value: 1.23 },
            localVue: await createLocalVueForTests(),
        });
    });

    describe("without value", () => {
        beforeEach(() => wrapper.setProps({ value: null }));

        it("Shows nothing", () => {
            expect(wrapper.text()).toBe("");
        });
    });

    describe("when changing value", () => {
        beforeEach(() => {
            const input = wrapper.get("input");
            input.element.value = "4.56";
            input.trigger("input");
        });

        it("emits input event with corresponding value", () => {
            expect(wrapper.emitted().input).toBeTruthy();
            expect(wrapper.emitted().input[0]).toStrictEqual([4.56]);
        });
    });

    describe("when trying to input not float value", () => {
        beforeEach(() => {
            const input = wrapper.get("input");
            input.element.value = "invalid format";
            input.trigger("input");
        });

        it("does not emit input event", () => {
            expect(wrapper.emitted().input).toBeFalsy();
        });
    });
});
