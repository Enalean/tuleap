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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseDisplayer from "./ReleaseDisplayer.vue";
import ReleaseHeader from "./ReleaseHeader/ReleaseHeader.vue";
import type { MilestoneData } from "../../type";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { jest } from "@jest/globals";

let release_data: MilestoneData;
let is_open = false;
const get_enhanced_release_mock = jest.fn();

jest.useFakeTimers();

describe("ReleaseDisplayer", () => {
    function getPersonalWidgetInstance(): VueWrapper<InstanceType<typeof ReleaseDisplayer>> {
        const useStore = defineStore("root", {
            state: () => ({}),
            actions: {
                getEnhancedMilestones: get_enhanced_release_mock,
            },
        });
        const pinia = createTestingPinia();
        useStore(pinia);

        release_data = {
            label: "mile",
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            capacity: 10,
            total_sprint: 20,
            initial_effort: 10,
        } as MilestoneData;

        const component_options = {
            global: {
                ...getGlobalTestOptions(pinia),
            },
            propsData: {
                release_data,
                isOpen: is_open,
                isPastRelease: false,
            },
        };

        return shallowMount(ReleaseDisplayer, component_options);
    }

    it("When the widget is rendered and it's the first release, Then toggle is open", async () => {
        is_open = true;
        const wrapper = getPersonalWidgetInstance();
        await jest.runOnlyPendingTimersAsync();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(true);
    });

    it("When the widget is rendered and it's not the first release, Then toggle is closed", () => {
        const wrapper = getPersonalWidgetInstance();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(false);
    });

    it("When the toggle is opened and the user want close it, Then an event is emit", async () => {
        is_open = true;

        const wrapper = getPersonalWidgetInstance();
        await jest.runOnlyPendingTimersAsync();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(true);

        wrapper.findComponent(ReleaseHeader).vm.$emit("toggle-release-details");
        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(false);
    });

    it("When the milestone is loading, Then the class is disabled and a tooltip say why", () => {
        const wrapper = getPersonalWidgetInstance();
        expect(wrapper.attributes("data-tlp-tooltip")).toBe("Loading data...");
    });

    it("When the widget is rendered and the toggle opened, Then there are no errors and components called", async () => {
        is_open = true;

        const wrapper = getPersonalWidgetInstance();

        await jest.runOnlyPendingTimersAsync();
        expect(wrapper.find("[data-test=display-release-data]").exists()).toBe(true);
    });
});
