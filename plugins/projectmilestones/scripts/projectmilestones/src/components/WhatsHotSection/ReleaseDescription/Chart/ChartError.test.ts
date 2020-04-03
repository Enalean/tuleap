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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { StoreOptions } from "../../../../type";
import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { createReleaseWidgetLocalVue } from "../../../../helpers/local-vue-for-test";
import ChartError from "./ChartError.vue";

const component_options: ShallowMountOptions<ChartError> = {};
const message_error_duration = "'duration' field is empty or invalid.";
const message_error_start_date = "'start_date' field is empty or invalid.";
const message_error_under_calculation =
    "Burndown is under calculation. It will be available in a few minutes.";

describe("ChartError", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ChartError>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ChartError, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {},
        };

        component_options.propsData = {
            has_error_duration: true,
            has_error_start_date: true,
            is_under_calculation: true,
            message_error_duration,
            message_error_start_date,
            message_error_under_calculation,
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When there are 3 errors, Then error caused by 'under calculation' is not displayed", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=error-duration]")).toBe(true);
        expect(wrapper.contains("[data-test=error-calculation]")).toBe(false);
        expect(wrapper.contains("[data-test=error-start-date]")).toBe(true);
    });

    it("When there are an error on start date and duration, Then they are displayed", async () => {
        component_options.propsData = {
            has_error_duration: true,
            has_error_start_date: true,
            is_under_calculation: false,
            message_error_duration,
            message_error_start_date,
            message_error_under_calculation,
        };
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=error-duration]")).toBe(true);
        expect(wrapper.contains("[data-test=error-calculation]")).toBe(false);
        expect(wrapper.contains("[data-test=error-start-date]")).toBe(true);
    });

    it("When there are an error on start date and calculation, Then only error on start date is displayed", async () => {
        component_options.propsData = {
            has_error_duration: false,
            has_error_start_date: true,
            is_under_calculation: true,
            message_error_duration,
            message_error_start_date,
            message_error_under_calculation,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=error-duration]")).toBe(false);
        expect(wrapper.contains("[data-test=error-calculation]")).toBe(false);
        expect(wrapper.contains("[data-test=error-start-date]")).toBe(true);
    });

    it("When there are an error on duration and calculation, Then only error on duration is displayed", async () => {
        component_options.propsData = {
            has_error_duration: true,
            has_error_start_date: false,
            is_under_calculation: true,
            message_error_duration,
            message_error_start_date,
            message_error_under_calculation,
        };
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=error-duration]")).toBe(true);
        expect(wrapper.contains("[data-test=error-calculation]")).toBe(false);
        expect(wrapper.contains("[data-test=error-start-date]")).toBe(false);
    });

    it("When there are only an error on calculation, Then it is displayed", async () => {
        component_options.propsData = {
            has_error_duration: false,
            has_error_start_date: false,
            is_under_calculation: true,
            message_error_duration,
            message_error_start_date,
            message_error_under_calculation,
        };
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=error-duration]")).toBe(false);
        expect(wrapper.contains("[data-test=error-calculation]")).toBe(true);
        expect(wrapper.contains("[data-test=error-start-date]")).toBe(false);
    });

    it("When there are only an error on duration, Then it is displayed", async () => {
        component_options.propsData = {
            has_error_duration: true,
            has_error_start_date: false,
            is_under_calculation: false,
            message_error_duration,
            message_error_start_date,
            message_error_under_calculation,
        };
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=error-duration]")).toBe(true);
        expect(wrapper.contains("[data-test=error-calculation]")).toBe(false);
        expect(wrapper.contains("[data-test=error-start-date]")).toBe(false);
    });

    it("When there are only an error on start date, Then it is displayed", async () => {
        component_options.propsData = {
            has_error_duration: false,
            has_error_start_date: true,
            is_under_calculation: false,
            message_error_duration,
            message_error_start_date,
            message_error_under_calculation,
        };
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=error-duration]")).toBe(false);
        expect(wrapper.contains("[data-test=error-calculation]")).toBe(false);
        expect(wrapper.contains("[data-test=error-start-date]")).toBe(true);
    });
});
