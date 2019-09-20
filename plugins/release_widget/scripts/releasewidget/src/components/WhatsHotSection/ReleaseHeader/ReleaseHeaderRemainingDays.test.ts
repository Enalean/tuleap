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

import Vue from "vue";
import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import ReleaseHeaderRemainingDays from "./ReleaseHeaderRemainingDays.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../type";
import { initVueGettext } from "../../../../../../../../src/www/scripts/tuleap/gettext/vue-gettext-init";

let releaseData: MilestoneData;
let component_options: ShallowMountOptions<ReleaseHeaderRemainingDays>;

describe("ReleaseHeaderRemainingDays", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseHeaderRemainingDays>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        await initVueGettext(Vue, () => {
            throw new Error("Fallback to default");
        });

        return shallowMount(ReleaseHeaderRemainingDays, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            label: "mile",
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            capacity: 10,
            number_of_artifact_by_trackers: []
        };

        component_options = {
            propsData: {
                releaseData
            }
        };
    });

    describe("Display remaining days", () => {
        it("When there is number of start days but equal at 0, Then number days of end is displayed and percent in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                number_days_until_end: 10,
                number_days_since_start: 0,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there isn't number of start days, Then 0 is displayed and a message in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there is negative number of start days, Then 0 is displayed and 0.00% in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                number_days_until_end: -10,
                number_days_since_start: -10,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there is negative remaining days, Then 0 is displayed and 100% in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                start_date: null,
                number_days_until_end: -10,
                number_days_since_start: 10,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there isn't remaining days, Then 0 is displayed and there is a message in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                start_date: null,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there is remaining days but equal at 0, Then remaining days is displayed and percent in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                start_date: null,
                number_days_until_end: 0,
                number_days_since_start: 10,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there is remaining days and is null, Then 0 is displayed and there is a message in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                start_date: null,
                number_days_since_start: 10,
                number_days_until_end: null,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });

        it("When there is remaining days, not null and greater than 0, Then remaining days is displayed and percent in tooltip", async () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                start_date: null,
                number_days_until_end: 5,
                number_days_since_start: 5,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = await getPersonalWidgetInstance(store_options);
            expect(wrapper.element).toMatchSnapshot();
        });
    });
});
