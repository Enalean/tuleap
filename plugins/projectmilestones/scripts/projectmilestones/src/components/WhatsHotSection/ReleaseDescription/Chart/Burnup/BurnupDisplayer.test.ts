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

import type { BurnupData, MilestoneData, PointsWithDateForBurnup } from "../../../../../type";
import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createReleaseWidgetLocalVue } from "../../../../../helpers/local-vue-for-test";
import ChartError from "../ChartError.vue";
import BurnupDisplayer from "./BurnupDisplayer.vue";
import BurnupChart from "./BurnupChart.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

describe("BurnupDisplayer", () => {
    async function getPersonalWidgetInstance(
        start_date: string | null,
        end_date: string | null,
        burnup_data: BurnupData,
        is_timeframe_duration = true,
    ): Promise<Wrapper<BurnupDisplayer>> {
        const useStore = defineStore("root", {
            state: () => ({
                label_timeframe: "timeframe_field",
                label_start_date: "start_date_field",
                is_timeframe_duration,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        const component_options: ShallowMountOptions<BurnupDisplayer> = {};
        component_options.localVue = await createReleaseWidgetLocalVue();

        const release_data = {
            id: 2,
            start_date,
            end_date,
            burnup_data,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
            burnup_data,
        };

        return shallowMount(BurnupDisplayer, component_options);
    }

    it("When the burnup is under calculation, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burnup_data = {
            start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: true,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;
        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeTruthy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there isn't start date, Then ChartError component is rendered", async () => {
        const start_date = null;
        const end_date = null;
        const burnup_data = {
            start_date: "",
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeTruthy();
        expect(chart_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there duration is equal to 0, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burnup_data = {
            start_date,
            duration: 0,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burnup_data = {
            start_date,
            duration: null,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and start date is null, Then ChartError component is rendered", async () => {
        const start_date = null;
        const end_date = null;
        const burnup_data = {
            start_date: "",
            duration: null,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeTruthy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and it is under calculation, Then ChartError component is rendered", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burnup_data = {
            start_date,
            duration: null,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: true,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeTruthy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the burnup can be created, Then a message is displayed", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burnup_data = {
            start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data);

        expect(wrapper.findComponent(BurnupChart).exists()).toBe(true);
    });

    it("When the timeframe is not on duration field and end date field is null, Then there is an error", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = null;
        const burnup_data = {
            start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data, false);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the timeframe is not on duration field and there is end date, Then there is no error", async () => {
        const start_date = new Date("2017-01-22T13:42:08+02:00").toDateString();
        const end_date = new Date("2019-02-05T11:41:01+02:00").toDateString();
        const burnup_data = {
            start_date,
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: false,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurnup[],
            label: "burnup",
            points_with_date_count_elements: [],
        } as BurnupData;

        const wrapper = await getPersonalWidgetInstance(start_date, end_date, burnup_data, false);

        expect(wrapper.findComponent(ChartError).exists()).toBeFalsy();
    });
});
