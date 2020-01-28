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

import { BurndownData, MilestoneData, PointsWithDate, StoreOptions } from "../../../../type";
import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import ChartDisplayer from "./ChartDisplayer.vue";
import { createReleaseWidgetLocalVue } from "../../../../helpers/local-vue-for-test";
import { DefaultData } from "vue/types/options";
import BurndownDisplayer from "./Burndown/BurndownDisplayer.vue";
import * as rest_querier from "../../../../api/rest-querier";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ChartDisplayer> = {};
const project_id = 102;

describe("ChartDisplayer", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ChartDisplayer>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ChartDisplayer, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id,
                is_timeframe_duration: true
            }
        };

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
                points_with_date: [] as PointsWithDate[]
            } as BurndownData,
            resources: {}
        } as MilestoneData;

        component_options.propsData = {
            release_data
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When the burndown can be created, Then component BurndownDisplayer is rendered", async () => {
        component_options.propsData = {
            release_data,
            is_loading: false
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(BurndownDisplayer)).toBe(true);
    });

    it("When the burndown is recovering, Then component BurndownDisplayer is rendered", async () => {
        const burndown_data = {
            start_date: new Date().toString(),
            duration: 10,
            capacity: 10,
            points: [] as number[],
            is_under_calculation: true,
            opening_days: [] as number[],
            points_with_date: [] as PointsWithDate[]
        } as BurndownData;

        jest.spyOn(rest_querier, "getBurndownData").mockReturnValue(
            Promise.resolve({
                values: [
                    {
                        value: burndown_data,
                        field_id: 10,
                        label: "burndown",
                        type: "burndown"
                    }
                ]
            })
        );

        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burndown_data: null,
            resources: {}
        } as MilestoneData;

        component_options.data = (): DefaultData<BurndownDisplayer> => {
            return {
                is_open: false,
                is_loading: true,
                error_message: null
            };
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(BurndownDisplayer)).toBe(true);
    });

    it("When the burndown doesn't yet exist, Then there is a spinner", async () => {
        component_options.data = (): DefaultData<BurndownDisplayer> => {
            return {
                is_open: false,
                is_loading: true,
                error_message: null
            };
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        wrapper.setData({ is_loading: true });

        expect(wrapper.contains("[data-test=loading-data]")).toBe(true);
    });

    it("When there is a rest error, Then BurndownDisplayer is rendered with an error message", async () => {
        component_options.data = (): DefaultData<BurndownDisplayer> => {
            return {
                is_open: false,
                is_loading: false,
                message_error_rest: "404 Error"
            };
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        const burndown_chart = wrapper.find(BurndownDisplayer);

        expect(burndown_chart.attributes("message_error_rest")).toBe("404 Error");
    });
});
