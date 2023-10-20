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
} from "../../../../type";
import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ChartDisplayer from "./ChartDisplayer.vue";
import { createReleaseWidgetLocalVue } from "../../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import BurndownDisplayer from "./Burndown/BurndownDisplayer.vue";
import * as rest_querier from "../../../../api/rest-querier";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ChartDisplayer> = {};
const project_id = 102;

describe("ChartDisplayer", () => {
    async function getPersonalWidgetInstance(): Promise<Wrapper<ChartDisplayer>> {
        const useStore = defineStore("root", {
            state: () => ({
                project_id,
                is_timeframe_duration: true,
            }),
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ChartDisplayer, component_options);
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
            burnup_data: {} as BurnupData,
            resources: {},
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        getPersonalWidgetInstance();
    });

    it("When the burndown can be created, Then component BurndownDisplayer is rendered", async () => {
        component_options.propsData = {
            release_data,
            is_loading: false,
        };

        const wrapper = await getPersonalWidgetInstance();

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

        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: null,
            resources: {},
        } as MilestoneData;

        component_options.data = (): DefaultData<BurndownDisplayer> => {
            return {
                is_open: false,
                is_loading: true,
                error_message: null,
            };
        };

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BurndownDisplayer).exists()).toBe(true);
        expect(wrapper.find("[data-test=burnup-exists]").exists()).toBe(true);
    });

    it("When the burnup doesn't exist, Then there is nothing", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: null,
            burnup_data: null,
            resources: {},
        } as MilestoneData;

        component_options.data = (): DefaultData<BurndownDisplayer> => {
            return {
                is_loading: false,
            };
        };

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.find("[data-test=burnup-exists]").exists()).toBe(false);
    });

    it("When the burndown doesn't yet exist, Then there is a spinner", async () => {
        component_options.data = (): DefaultData<BurndownDisplayer> => {
            return {
                is_open: false,
                is_loading: true,
                error_message: null,
            };
        };

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();
        wrapper.setData({ is_loading: true });
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=loading-data]").exists()).toBe(true);
    });

    it("When there is a rest error, Then the error is displayed", async () => {
        component_options.data = (): DefaultData<BurndownDisplayer> => {
            return {
                is_open: false,
                is_loading: false,
                message_error_rest: "404 Error",
                has_rest_error: true,
            };
        };

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance();

        expect(wrapper.get("[data-test=error-rest]").text()).toBe("404 Error");
    });
});
