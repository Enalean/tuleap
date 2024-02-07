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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseDisplayer from "./ReleaseDisplayer.vue";
import ReleaseHeader from "./ReleaseHeader/ReleaseHeader.vue";
import type { MilestoneData } from "../../type";
import { createReleaseWidgetLocalVue } from "../../helpers/local-vue-for-test";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import Vue from "vue";

let release_data: MilestoneData;
let is_open = false;

describe("ReleaseDisplayer", () => {
    async function getPersonalWidgetInstance(): Promise<Wrapper<Vue, Element>> {
        const useStore = defineStore("root", {
            state: () => ({}),
            actions: {
                getEnhancedMilestones: jest.fn(),
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
            localVue: await createReleaseWidgetLocalVue(),
            pinia,
            propsData: {
                release_data,
                isOpen: is_open,
                isPastRelease: false,
            },
        };

        return shallowMount(ReleaseDisplayer, component_options);
    }

    it("When there is a rest error, Then it displays", async () => {
        is_open = true;
        const wrapper = await getPersonalWidgetInstance();
        wrapper.setData({ error_message: "404" });
        await Vue.nextTick();

        expect(wrapper.find("[data-test=show-error-message]").exists()).toBe(true);
    });

    it("When the widget is rendered and it's the first release, Then toggle is open", async () => {
        const wrapper = await getPersonalWidgetInstance();
        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(true);
    });

    it("When the widget is rendered and it's not the first release, Then toggle is closed", async () => {
        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(false);
    });

    it("When the toggle is opened and the user want close it, Then an event is emit", async () => {
        is_open = true;

        const wrapper = await getPersonalWidgetInstance();
        await Vue.nextTick();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(true);

        wrapper.findComponent(ReleaseHeader).vm.$emit("toggle-release-details");
        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=toggle-open]").exists()).toBe(false);
    });

    it("When the milestone is loading, Then the class is disabled and a tooltip say why", async () => {
        const wrapper = await getPersonalWidgetInstance();
        wrapper.setData({ is_loading: true });
        expect(wrapper.attributes("data-tlp-tooltip")).toBe("Loading data...");
    });

    it("When the widget is rendered and the toggle opened, Then there are no errors and components called", async () => {
        is_open = true;

        const wrapper = await getPersonalWidgetInstance();

        await Vue.nextTick();
        expect(wrapper.find("[data-test=display-release-data]").exists()).toBe(true);
    });
});
