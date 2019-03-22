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
import HumanizedDate from "./HumanizedDate.vue";

describe("HumanizedDate", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(HumanizedDate, {
            localVue,
            propsData: {
                date: "2019-03-22T09:01:48+00:00"
            }
        });
    });

    it("shows humanized date", () => {
        expect(wrapper.text()).toEqual("March 22, 2019 at 10h 01mn 48s");
    });

    it("pads hour until 9", () => {
        expect(wrapper.vm.padHour(9)).toEqual("09");
    });

    it("does not pad hour after 10", () => {
        expect(wrapper.vm.padHour(10)).toEqual(10);
    });
});
