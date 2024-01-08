/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import WidgetModalEditTime from "./WidgetModalEditTime.vue";
import { createLocalVueForTests } from "../../helpers/local-vue.js";

describe("Given a personal timetracking widget modal", () => {
    let current_artifact = { artifact: "artifact", id: 10 };
    let times = {};

    async function getWrapperInstance(time_data = {}) {
        const component_options = {
            localVue: await createLocalVueForTests(),
            propsData: {
                timeData: time_data,
                artifact: current_artifact,
            },
        };
        return shallowMount(WidgetModalEditTime, component_options);
    }

    describe("Initialisation", () => {
        it("When no date is given, then it should be initialized", async () => {
            times.date = undefined;
            const wrapper = await getWrapperInstance();
            expect(wrapper.vm.date).toBeDefined();
        });

        it("When a date is given, then it should use it", async () => {
            const date = "2023-10-30";
            const wrapper = await getWrapperInstance({
                date,
            });

            expect(wrapper.vm.date).toBe(date);
        });
    });

    describe("Submit", () => {
        it("Given a new time is not filled, then the time is invalid", async () => {
            const wrapper = await getWrapperInstance();
            wrapper.setData({ time: null });

            wrapper.find("[data-test=timetracking-submit-time]").trigger("click");

            expect(wrapper.vm.error_message).toBe("Time is required");
        });

        it("Given a new time is submitted with an incorrect format, then the time is invalid", async () => {
            const wrapper = await getWrapperInstance();
            wrapper.setData({ time: "00" });
            wrapper.find("[data-test=timetracking-submit-time]").trigger("click");

            expect(wrapper.vm.error_message).toBe("Please check time's format (hh:mm)");
        });

        it("Given a new time is submitted, then the submit button is disabled and a new event is sent", async () => {
            const wrapper = await getWrapperInstance();

            jest.spyOn(wrapper.vm, "$emit").mockImplementation(() => {});

            wrapper.setData({
                date: "2020-04-03",
                time: "00:10",
            });
            wrapper.find("[data-test=timetracking-submit-time]").trigger("click");

            expect(wrapper.vm.$emit).toHaveBeenCalledWith(
                "validate-time",
                "2020-04-03",
                10,
                "00:10",
                "",
            );
            expect(wrapper.vm.is_loading).toBe(true);
        });
    });
});
