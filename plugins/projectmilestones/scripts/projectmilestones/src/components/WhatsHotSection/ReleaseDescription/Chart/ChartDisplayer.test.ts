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

import type {
    BurndownData,
    BurnupData,
    MilestoneData,
    PointsWithDateForBurndown,
    ArtifactMilestoneChartBurnup,
} from "../../../../type";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ChartDisplayer from "./ChartDisplayer.vue";
import { createReleaseWidgetLocalVue } from "../../../../helpers/local-vue-for-test";
import BurndownDisplayer from "./Burndown/BurndownDisplayer.vue";
import * as rest_querier from "../../../../api/rest-querier";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const project_id = 102;

describe("ChartDisplayer", () => {
    async function getPersonalWidgetInstance(
        burndown_data: BurndownData | null,
        burnup_data: BurnupData | null,
    ): Promise<Wrapper<Vue, Element>> {
        const release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data,
            burnup_data,
            resources: {},
        } as MilestoneData;

        const useStore = defineStore("root", {
            state: () => ({
                project_id,
                is_timeframe_duration: true,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ChartDisplayer, {
            propsData: {
                release_data,
            },
            localVue: await createReleaseWidgetLocalVue(),
        });
    }

    it("When the burndown can be created, Then component BurndownDisplayer is rendered", async () => {
        const burndown_data = {
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: true,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        jest.spyOn(rest_querier, "getChartData").mockReturnValue(
            Promise.resolve({
                values: [
                    {
                        value: burndown_data,
                        field_id: 10,
                        label: "burndown",
                        type: "burndown",
                    },
                    {} as ArtifactMilestoneChartBurnup,
                ],
            }),
        );

        const wrapper = await getPersonalWidgetInstance(burndown_data, {} as BurnupData);
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BurndownDisplayer).exists()).toBe(true);
    });

    it("When the charts are recovering, Then component BurndownDisplayer is rendered", async () => {
        const burndown_data = {
            start_date: new Date().toString(),
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: true,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDateForBurndown[],
        } as BurndownData;

        const burnup_data = {
            start_date: new Date().toString(),
        } as BurnupData;

        jest.spyOn(rest_querier, "getChartData").mockReturnValue(
            Promise.resolve({
                values: [
                    {
                        value: burndown_data,
                        field_id: 10,
                        label: "burndown",
                        type: "burndown",
                    },
                    {
                        value: burnup_data,
                        field_id: 12,
                        label: "burnup",
                        type: "burnup",
                    },
                ],
            }),
        );

        const wrapper = await getPersonalWidgetInstance(null, null);
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BurndownDisplayer).exists()).toBe(true);
        expect(wrapper.find("[data-test=burnup-exists]").exists()).toBe(true);
    });

    it("When the burnup doesn't exist, Then there is nothing", async () => {
        const wrapper = await getPersonalWidgetInstance(null, null);

        expect(wrapper.find("[data-test=burnup-exists]").exists()).toBe(false);
    });

    it("When the burndown doesn't yet exist, Then there is a spinner", async () => {
        const wrapper = await getPersonalWidgetInstance(null, null);
        expect(wrapper.find("[data-test=loading-data]").exists()).toBe(true);

        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=loading-data]").exists()).toBe(false);
    });

    it("When there is a rest error, Then the error is displayed", async () => {
        const response = {
            json(): Promise<Record<string, unknown>> {
                return Promise.resolve({ error: { code: 404, message: "Error" } });
            },
        } as Response;
        jest.spyOn(rest_querier, "getChartData").mockReturnValue(
            Promise.reject(new FetchWrapperError("404 Error", response)),
        );

        const wrapper = await getPersonalWidgetInstance(null, null);
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.get("[data-test=error-rest]").text()).toBe("404 Error");
    });
});
