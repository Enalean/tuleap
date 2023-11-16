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

const project_id = 102;

describe("BurndownDisplayer", () => {
    async function getPersonalWidgetInstance(
        start_date: string | null,
        end_date: string | null,
        burndown_data: BurndownData,
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

        const component_options: ShallowMountOptions<BurndownDisplayer> = {};
        component_options.localVue = await createReleaseWidgetLocalVue();

        const release_data = {
            id: 2,
            start_date,
            end_date,
            burndown_data,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
            burndown_data,
        };

        return shallowMount(BurndownDisplayer, component_options);
    }

    it("When the burndown is under calculation, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burndown_data = {
            start_date: start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: true,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;
        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeTruthy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there isn't start date, Then ChartError component is rendered", async () => {
        const start_date = null;
        const end_date = null;
        const burndown_data = {
            start_date: "",
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeTruthy();
        expect(burndown_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there duration is equal to 0, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burndown_data = {
            start_date: start_date,
            duration: 0,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burndown_data = {
            start_date: start_date,
            duration: null,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and start date is null, Then ChartError component is rendered", async () => {
        const start_date = null;
        const end_date = null;
        const burndown_data = {
            start_date: "",
            duration: null,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeTruthy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and it is under calculation, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burndown_data = {
            start_date: start_date,
            duration: null,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: true,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeTruthy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the burndown can be created, Then component BurndownDisplayer is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burndown_data = {
            start_date: start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data);

        expect(wrapper.findComponent(Burndown).exists()).toBe(true);
    });

    it("When the timeframe is not on duration field and end date field is null, Then there is an error", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burndown_data = {
            start_date: start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data, false);
        const burndown_error = wrapper.findComponent(ChartError);

        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the timeframe is not on duration field and there is end date, Then there is no error", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = new Date("2019-02-05T11:41:01+02:00").toDateString();
        const burndown_data = {
            start_date: start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burndown_data, false);

        expect(wrapper.findComponent(ChartError).exists()).toBeFalsy();
    });
});
