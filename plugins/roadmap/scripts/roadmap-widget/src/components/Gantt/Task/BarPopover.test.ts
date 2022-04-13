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
import BarPopover from "./BarPopover.vue";
import { createRoadmapLocalVue } from "../../../helpers/local-vue-for-test";
import type { Task } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("BarPopover", () => {
    it("should display the info of the task", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: new Date("2020-01-30T15:00:00.000Z"),
                    progress: null,
                    progress_error_message: "",
                    is_milestone: false,
                    time_period_error_message: "",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.classes()).not.toContain("roadmap-gantt-task-milestone-popover");
        expect(wrapper.text()).toContain("art #123");
        expect(wrapper.text()).toContain("Create button");
        expect(wrapper.text()).toContain("January 12, 2020");
        expect(wrapper.text()).toContain("January 30, 2020");
        expect(wrapper.find("[data-test=progress]").exists()).toBeFalsy();
    });

    it("should add special appearance for a milestone", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: new Date("2020-01-30T15:00:00.000Z"),
                    progress: null,
                    progress_error_message: "",
                    is_milestone: true,
                    time_period_error_message: "",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.classes()).toContain("roadmap-gantt-task-milestone-popover");
    });

    it("should display the progress", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: new Date("2020-01-30T15:00:00.000Z"),
                    progress: 0.42123,
                    progress_error_message: "",
                    time_period_error_message: "",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=progress]").text()).toContain("42%");
    });

    it("should display the progress error message", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: new Date("2020-01-30T15:00:00.000Z"),
                    progress: null,
                    progress_error_message: "You fucked up!",
                    time_period_error_message: "",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=progress]").text()).toContain("You fucked up!");
    });

    it("should display undefined if no start date", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: null,
                    end: new Date("2020-01-30T15:00:00.000Z"),
                    progress: null,
                    progress_error_message: "",
                    time_period_error_message: "",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("Undefined");
        expect(wrapper.text()).toContain("January 30, 2020");
    });

    it("should display undefined if no end date", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: null,
                    progress: null,
                    progress_error_message: "",
                    time_period_error_message: "",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("January 12, 2020");
        expect(wrapper.text()).toContain("Undefined");
    });

    it("should display error message if end date < start date", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: new Date("2020-01-10T15:00:00.000Z"),
                    progress: null,
                    progress_error_message: "",
                    time_period_error_message: "",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("End date is lesser than start date!");
    });

    it("should display the time period error message", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: null,
                    end: null,
                    progress: null,
                    progress_error_message: "",
                    time_period_error_message: "The time period is fucked up",
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("The time period is fucked up");
    });
});
