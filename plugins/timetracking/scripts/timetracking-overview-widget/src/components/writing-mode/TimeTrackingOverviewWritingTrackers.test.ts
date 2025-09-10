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

import { describe, beforeEach, it, expect, jest } from "@jest/globals";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import type { ProjectTracker } from "../../store/state";
import TimeTrackingOverviewWritingTrackers from "./TimeTrackingOverviewWritingTrackers.vue";
import TimeTrackingOverviewTrackersOptions from "./TimeTrackingOverviewTrackersOptions.vue";

describe("Given a timetracking overview widget on writing mode", () => {
    let projects: ProjectReference[],
        trackers: ProjectTracker[],
        is_loading_trackers: boolean,
        has_success_message: boolean,
        addSelectedTrackers: jest.Mock;

    beforeEach(() => {
        projects = [{ label: "leprojet" } as ProjectReference];
        trackers = [{ label: "letracker" } as ProjectTracker];
        is_loading_trackers = false;
        has_success_message = false;
        addSelectedTrackers = jest.fn();
    });

    const getWrapper = (): VueWrapper => {
        const useStore = defineStore("overview/1", {
            state: () => ({
                projects,
                trackers,
                is_loading_trackers,
            }),
            getters: {
                has_success_message: () => has_success_message,
            },
            actions: {
                addSelectedTrackers,
            },
        });

        const pinia = createTestingPinia({ stubActions: false });
        useStore(pinia);

        return shallowMount(TimeTrackingOverviewWritingTrackers, {
            global: getGlobalTestOptions(pinia),
        });
    };

    it("When trackers and projects are available, then it's possible to click on add button", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(false);
        expect(wrapper.find("[data-test=icon-plus]").exists()).toBe(true);
        expect(wrapper.find("[data-test=icon-ban]").exists()).toBe(false);
    });

    it("When trackers and projects are available, then click on add button", () => {
        const wrapper = getWrapper();
        const selected_tracker_id = 15;

        wrapper
            .findComponent(TimeTrackingOverviewTrackersOptions)
            .vm.$emit("input", String(selected_tracker_id));
        wrapper.get("[data-test=add-tracker-button]").trigger("click");
        expect(addSelectedTrackers).toHaveBeenCalledWith(selected_tracker_id);
    });

    it("When trackers are not available, then ban icon is displayed", () => {
        trackers = [];

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(false);
        expect(wrapper.find("[data-test=icon-plus]").exists()).toBe(false);
        expect(wrapper.find("[data-test=icon-ban]").exists()).toBe(true);
    });

    it("When projects are not available, then spinner icon is displayed", () => {
        projects = [];
        is_loading_trackers = true;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=icon-spinner]").exists()).toBe(true);
        expect(wrapper.find("[data-test=icon-plus]").exists()).toBe(false);
        expect(wrapper.find("[data-test=icon-ban]").exists()).toBe(false);
    });
});
