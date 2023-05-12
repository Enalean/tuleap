/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import App from "./App.vue";
import type { Tracker } from "../type";
import * as list_picker from "@tuleap/list-picker";
import { createGettext } from "vue3-gettext";

describe("App", () => {
    beforeEach(() => {
        jest.spyOn(list_picker, "createListPicker").mockReturnValue({
            destroy: () => {
                // Nothing to do since we did not really create something
            },
        });
    });

    it("should select a tracker that is not already a lvl1 iteration", async () => {
        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                widget_id: 101,
                title: "Roadmap",
                trackers: [
                    { id: 1, title: "Releases" },
                    { id: 2, title: "Sprints" },
                    { id: 3, title: "Epics" },
                    { id: 4, title: "Stories" },
                ] as Tracker[],
                selected_tracker_ids: [],
                selected_lvl1_iteration_tracker_id: 1,
                selected_lvl2_iteration_tracker_id: "",
                is_in_creation: false,
                selected_default_timescale: "week",
            },
        });
        await wrapper.vm.$nextTick();

        const select = wrapper.find("[data-test=tracker]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        expect(
            Array.from(select.options)
                .map((option: HTMLOptionElement) => option.value)
                .sort()
        ).toStrictEqual(["2", "3", "4"]);
    });

    it("should select a tracker that is not already a lvl2 iteration", async () => {
        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                widget_id: 101,
                title: "Roadmap",
                trackers: [
                    { id: 1, title: "Releases" },
                    { id: 2, title: "Sprints" },
                    { id: 3, title: "Epics" },
                    { id: 4, title: "Stories" },
                ] as Tracker[],
                selected_tracker_ids: [],
                selected_lvl1_iteration_tracker_id: 1,
                selected_lvl2_iteration_tracker_id: 2,
                is_in_creation: false,
                selected_default_timescale: "week",
            },
        });
        await wrapper.vm.$nextTick();

        const select = wrapper.find("[data-test=tracker]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        expect(
            Array.from(select.options)
                .map((option: HTMLOptionElement) => option.value)
                .sort()
        ).toStrictEqual(["3", "4"]);
    });

    it("should select a lvl1 tracker that is not already a lvl2 iteration", async () => {
        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                widget_id: 101,
                title: "Roadmap",
                trackers: [
                    { id: 1, title: "Releases" },
                    { id: 2, title: "Sprints" },
                    { id: 3, title: "Epics" },
                    { id: 4, title: "Stories" },
                ] as Tracker[],
                selected_tracker_ids: [],
                selected_lvl1_iteration_tracker_id: 1,
                selected_lvl2_iteration_tracker_id: 2,
                is_in_creation: false,
                selected_default_timescale: "week",
            },
        });
        await wrapper.vm.$nextTick();

        const select = wrapper.find("[data-test=lvl1-iteration-tracker]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        expect(
            Array.from(select.options)
                .map((option: HTMLOptionElement) => option.value)
                .sort()
        ).toStrictEqual(["", "1", "3", "4"]);
    });

    it("should select a lvl1 tracker that is not already a lvl2 iteration nor a tracker", async () => {
        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                widget_id: 101,
                title: "Roadmap",
                trackers: [
                    { id: 1, title: "Releases" },
                    { id: 2, title: "Sprints" },
                    { id: 3, title: "Epics" },
                    { id: 4, title: "Stories" },
                ] as Tracker[],
                selected_tracker_ids: [3],
                selected_lvl1_iteration_tracker_id: 1,
                selected_lvl2_iteration_tracker_id: 2,
                is_in_creation: false,
                selected_default_timescale: "week",
            },
        });
        await wrapper.vm.$nextTick();

        const select = wrapper.find("[data-test=lvl1-iteration-tracker]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        expect(
            Array.from(select.options)
                .map((option: HTMLOptionElement) => option.value)
                .sort()
        ).toStrictEqual(["", "1", "4"]);
    });

    it("should select a lvl2 tracker that is not already a lvl1 iteration", async () => {
        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                widget_id: 101,
                title: "Roadmap",
                trackers: [
                    { id: 1, title: "Releases" },
                    { id: 2, title: "Sprints" },
                    { id: 3, title: "Epics" },
                    { id: 4, title: "Stories" },
                ] as Tracker[],
                selected_tracker_ids: [],
                selected_lvl1_iteration_tracker_id: 1,
                selected_lvl2_iteration_tracker_id: 2,
                is_in_creation: false,
                selected_default_timescale: "week",
            },
        });
        await wrapper.vm.$nextTick();

        const select = wrapper.find("[data-test=lvl2-iteration-tracker]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        expect(
            Array.from(select.options)
                .map((option: HTMLOptionElement) => option.value)
                .sort()
        ).toStrictEqual(["", "2", "3", "4"]);
    });

    it("should select a lvl2 tracker that is not already a lvl1 iteration nor a tracker", async () => {
        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                widget_id: 101,
                title: "Roadmap",
                trackers: [
                    { id: 1, title: "Releases" },
                    { id: 2, title: "Sprints" },
                    { id: 3, title: "Epics" },
                    { id: 4, title: "Stories" },
                ] as Tracker[],
                selected_tracker_ids: [3],
                selected_lvl1_iteration_tracker_id: 1,
                selected_lvl2_iteration_tracker_id: 2,
                is_in_creation: false,
                selected_default_timescale: "week",
            },
        });
        await wrapper.vm.$nextTick();

        const select = wrapper.find("[data-test=lvl2-iteration-tracker]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        expect(
            Array.from(select.options)
                .map((option: HTMLOptionElement) => option.value)
                .sort()
        ).toStrictEqual(["", "2", "4"]);
    });

    it("should reset lvl2 tracker as soon as lvl1 tracker is reset", async () => {
        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                widget_id: 101,
                title: "Roadmap",
                trackers: [
                    { id: 1, title: "Releases" },
                    { id: 2, title: "Sprints" },
                    { id: 3, title: "Epics" },
                    { id: 4, title: "Stories" },
                ] as Tracker[],
                selected_tracker_ids: [3],
                selected_lvl1_iteration_tracker_id: 1,
                selected_lvl2_iteration_tracker_id: 2,
                is_in_creation: false,
                selected_default_timescale: "week",
            },
        });
        await wrapper.vm.$nextTick();

        const lvl1_select = wrapper.find("[data-test=lvl1-iteration-tracker]");
        if (!(lvl1_select.element instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        const lvl2_select = wrapper.find("[data-test=lvl2-iteration-tracker]").element;
        if (!(lvl2_select instanceof HTMLSelectElement)) {
            throw Error("Unable to find the select element");
        }

        expect(lvl2_select.value).toBe("2");

        await lvl1_select.setValue("");

        expect(lvl2_select.value).toBe("");
    });
});
