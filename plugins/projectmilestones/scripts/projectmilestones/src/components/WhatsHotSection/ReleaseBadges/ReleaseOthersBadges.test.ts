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

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const initial_effort = 10;
const component_options: ShallowMountOptions<ReleaseOthersBadges> = {};

const project_id = 102;

describe("ReleaseOthersBadges", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseOthersBadges>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseOthersBadges, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id,
            },
        };

        release_data = {
            id: 2,
            capacity: 10,
            total_sprint,
            initial_effort,
        } as MilestoneData;

        component_options.propsData = { release_data };
    });

    describe("Display points of initial effort", () => {
        it("When there is an initial effort, Then the points of initial effort are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBe(true);
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBe(false);
        });

        it("When there is initial effort but null, Then the points of initial effort are 'N/A'", async () => {
            release_data = {
                id: 2,
                capacity: 10,
                total_sprint,
                initial_effort: null,
            } as MilestoneData;

            component_options.propsData = { release_data };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=initial-effort-not-empty]")).toBe(false);
            expect(wrapper.contains("[data-test=initial-effort-empty]")).toBe(true);
        });
    });

    describe("Display points of capacity", () => {
        it("When there are points of capacity, Then the points of capacity are displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBe(true);
            expect(wrapper.contains("[data-test=capacity-empty]")).toBe(false);
        });

        it("When there are points of capacity but null, Then the points of capacity are 'N/A'", async () => {
            release_data = {
                id: 2,
                capacity: null,
                total_sprint,
                initial_effort,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=capacity-not-empty]")).toBe(false);
            expect(wrapper.contains("[data-test=capacity-empty]")).toBe(true);
        });
    });
});
