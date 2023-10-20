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

import type { BurndownData, MilestoneData, PointsWithDateForBurndown } from "../../../../../type";
import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import Burndown from "./Burndown.vue";
import { createReleaseWidgetLocalVue } from "../../../../../helpers/local-vue-for-test";
import ChartError from "../ChartError.vue";
import BurndownDisplayer from "./BurndownDisplayer.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<BurndownDisplayer> = {};
const project_id = 102;

describe("BurndownDisplayer", () => {
    async function getPersonalWidgetInstance(
        is_timeframe_duration = true,
    ): Promise<Wrapper<BurndownDisplayer>> {
        const useStore = defineStore("root", {
            state: () => ({
                project_id,
                is_timeframe_duration,
                label_timeframe: "Timeframe",
                label_start_date: "Start date",
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(BurndownDisplayer, component_options);
    }

    beforeEach(() => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: true,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        getPersonalWidgetInstance();
    });

    it("When the burndown is under calculation, Then ChartError component is rendered", async () => {
        component_options.propsData = {
            release_data,
        };
        const wrapper = await getPersonalWidgetInstance();
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeTruthy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there isn't start date, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: null,
            burndown_data: {
                start_date: "",
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeTruthy();
        expect(burndown_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there duration is equal to 0, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 0,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: null,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and start date is null, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: null,
            burndown_data: {
                start_date: "",
                duration: null,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeTruthy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and it is under calculation, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: null,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: true,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeTruthy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the burndown can be created, Then component BurndownDisplayer is rendered", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.findComponent(Burndown).exists()).toBe(true);
    });

    it("When the timeframe is not on duration field and end date field is null, Then there is an error", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            end_date: null,
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(false);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the timeframe is not on duration field and there is end date, Then there is no error", async () => {
        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            end_date: new Date("2019-02-05T11:41:01+02:00").toDateString(),
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurndown[],
            } as BurndownData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(false);

        expect(wrapper.findComponent(ChartError).exists()).toBeFalsy();
    });
});
