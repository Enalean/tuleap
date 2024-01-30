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

import { shallowMount } from "@vue/test-utils";
import TimeTrackingOverviewWritingTrackers from "./TimeTrackingOverviewWritingTrackers.vue";
import TimeTrackingOverviewTrackersOptions from "./TimeTrackingOverviewTrackersOptions.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createLocalVueForTests } from "../../../tests/helpers/local-vue.js";

describe("Given a timetracking overview widget on writing mode", () => {
    let component_options, store_options, store;
    beforeEach(async () => {
        store_options = {
            state: {
                projects: ["leprojet"],
                trackers: ["letracker"],
                is_loading_tracker: false,
            },
            getters: {
                has_success_message: false,
            },
        };
        store = createStoreMock(store_options);

        component_options = {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
        };
    });

    it("When trackers and projects are available, then it's possible to click on add button", () => {
        const wrapper = shallowMount(TimeTrackingOverviewWritingTrackers, component_options);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=icon-plus]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=icon-ban]").exists()).toBeFalsy();
    });

    it("When trackers and projects are available, then click on add button", () => {
        const wrapper = shallowMount(TimeTrackingOverviewWritingTrackers, component_options);

        wrapper.findComponent(TimeTrackingOverviewTrackersOptions).vm.$emit("input", "letracker");
        wrapper.get("[data-test=add-tracker-button]").trigger("click");
        expect(store.commit).toHaveBeenCalledWith("addSelectedTrackers", "letracker");
    });

    it("When trackers are not available, then ban icon is displayed", () => {
        store.state.trackers = [];

        const wrapper = shallowMount(TimeTrackingOverviewWritingTrackers, component_options);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=icon-plus]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=icon-ban]").exists()).toBeTruthy();
    });

    it("When projects are not available, then spinner icon is displayed", () => {
        store.state.projects = [];
        store.state.is_loading_tracker = true;

        const wrapper = shallowMount(TimeTrackingOverviewWritingTrackers, component_options);
        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=icon-plus]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=icon-ban]").exists()).toBeFalsy();
    });
});
