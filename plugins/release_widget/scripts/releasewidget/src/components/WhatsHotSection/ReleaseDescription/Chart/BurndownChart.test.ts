/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { MilestoneData, StoreOptions } from "../../../../type";
import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import BurndownChart from "./BurndownChart.vue";
import { createReleaseWidgetLocalVue } from "../../../../helpers/local-vue-for-test";
import { DefaultData } from "vue/types/options";
import * as rest_querier from "../../../../api/rest-querier";
import BurndownChartError from "./BurndownChartError.vue";
import BurndownChartDisplayer from "./BurndownChartDisplayer.vue";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<BurndownChart> = {};
const project_id = 102;

describe("BurndownChart", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<BurndownChart>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(BurndownChart, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            number_of_artifact_by_trackers: [],
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [],
                is_under_calculation: true,
                opening_days: [],
                points_with_date: []
            }
        };

        component_options.propsData = {
            release_data
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When the burndown is under calculation, Then BurndownChartError component is rendered", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);
        const burndown_error = wrapper.find(BurndownChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeTruthy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeFalsy();
        expect(burndown_error.attributes("has_error_rest")).toBeFalsy();
    });

    it("When there isn't start date, Then BurndownChartError component is rendered", async () => {
        store_options.state.project_id = project_id;
        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: null,
            number_of_artifact_by_trackers: [],
            burndown_data: {
                start_date: "",
                duration: 10,
                capacity: 10,
                points: [],
                is_under_calculation: false,
                opening_days: [],
                points_with_date: []
            }
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const burndown_error = wrapper.find(BurndownChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeTruthy();
        expect(burndown_error.attributes("has_error_duration")).toBeFalsy();
        expect(burndown_error.attributes("has_error_rest")).toBeFalsy();
    });

    it("When there isn't duration, Then BurndownChartError component is rendered", async () => {
        store_options.state.project_id = project_id;
        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            number_of_artifact_by_trackers: [],
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 0,
                capacity: 10,
                points: [],
                is_under_calculation: false,
                opening_days: [],
                points_with_date: []
            }
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const burndown_error = wrapper.find(BurndownChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
        expect(burndown_error.attributes("has_error_rest")).toBeFalsy();
    });

    it("When duration is null, Then BurndownChartError component is rendered", async () => {
        store_options.state.project_id = project_id;
        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            number_of_artifact_by_trackers: [],
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: null,
                capacity: 10,
                points: [],
                is_under_calculation: false,
                opening_days: [],
                points_with_date: []
            }
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const burndown_error = wrapper.find(BurndownChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
        expect(burndown_error.attributes("has_error_rest")).toBeFalsy();
    });

    it("When duration is null and there isn't start date, Then BurndownChartError component is rendered", async () => {
        store_options.state.project_id = project_id;
        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: null,
            number_of_artifact_by_trackers: [],
            burndown_data: {
                start_date: "",
                duration: null,
                capacity: 10,
                points: [],
                is_under_calculation: false,
                opening_days: [],
                points_with_date: []
            }
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const burndown_error = wrapper.find(BurndownChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeFalsy();
        expect(burndown_error.attributes("has_error_start_date")).toBeTruthy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
        expect(burndown_error.attributes("has_error_rest")).toBeFalsy();
    });

    it("When duration is null and it is under calculation, Then BurndownChartError component is rendered", async () => {
        store_options.state.project_id = project_id;
        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            number_of_artifact_by_trackers: [],
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: null,
                capacity: 10,
                points: [],
                is_under_calculation: true,
                opening_days: [],
                points_with_date: []
            }
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const burndown_error = wrapper.find(BurndownChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeTruthy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeTruthy();
        expect(burndown_error.attributes("has_error_rest")).toBeFalsy();
    });

    it("When the burndown can be created, Then component BurndownChartDisplayer is rendered", async () => {
        store_options.state.project_id = project_id;
        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            number_of_artifact_by_trackers: [],
            burndown_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [],
                is_under_calculation: false,
                opening_days: [],
                points_with_date: []
            }
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(BurndownChartDisplayer)).toBe(true);
    });

    it("When the burndown is recovering, Then component BurndownChartDisplayer is rendered", async () => {
        const burndown_data = {
            start_date: new Date().toString(),
            duration: 10,
            capacity: 10,
            points: [],
            is_under_calculation: false,
            opening_days: [],
            points_with_date: []
        };

        jest.spyOn(rest_querier, "getBurndownData").mockReturnValue(Promise.resolve(burndown_data));

        store_options.state.project_id = project_id;
        release_data = {
            id: 2,
            planning: {
                id: "100"
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            number_of_artifact_by_trackers: [],
            burndown_data: null
        };

        component_options.data = (): DefaultData<BurndownChart> => {
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

        expect(wrapper.contains(BurndownChartDisplayer)).toBe(true);
    });

    it("When the burndown doesn't yet exist, Then there is a spinner", async () => {
        component_options.data = (): DefaultData<BurndownChart> => {
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

    it("When there is a rest error, Then BurndownChartError component is rendered", async () => {
        component_options.data = (): DefaultData<BurndownChart> => {
            return {
                is_open: false,
                is_loading: true,
                message_error_rest: "404 Error"
            };
        };

        component_options.propsData = {
            release_data
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        wrapper.setData({ message_error_rest: "404 Error" });
        const burndown_error = wrapper.find(BurndownChartError);

        expect(burndown_error.attributes("is_under_calculation")).toBeTruthy();
        expect(burndown_error.attributes("has_error_start_date")).toBeFalsy();
        expect(burndown_error.attributes("has_error_duration")).toBeFalsy();
        expect(burndown_error.attributes("has_error_rest")).toBeTruthy();
    });
});
