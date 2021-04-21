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

import { createRoadmapLocalVue } from "../helpers/local-vue-for-test";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import NoDataToShowEmptyState from "./NoDataToShowEmptyState.vue";
import * as tlp from "tlp";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import SomethingWentWrongEmptyState from "./SomethingWentWrongEmptyState.vue";
import GanttBoard from "./Gantt/GanttBoard.vue";
import type { Task } from "../type";
import LoadingState from "./LoadingState.vue";

jest.mock("tlp");

describe("App", () => {
    async function mountComponent(): Promise<Wrapper<App>> {
        const wrapper = shallowMount(App, {
            propsData: {
                roadmap_id: 123,
                visible_natures: [],
            },
            localVue: await createRoadmapLocalVue(),
        });

        // wait for load & parse response
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        return wrapper;
    }

    it("Displays an empty state", async () => {
        jest.spyOn(tlp, "recursiveGet").mockResolvedValue([]);

        const wrapper = await mountComponent();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
    });

    it("Displays an error state for a 400", async () => {
        const recursive_get = jest.spyOn(tlp, "recursiveGet");
        mockFetchError(recursive_get, {
            status: 400,
            error_json: {
                error: {
                    i18n_error_message: "Missing timeframe",
                },
            },
        });

        const wrapper = await mountComponent();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);

        const error_state = wrapper.findComponent(SomethingWentWrongEmptyState);
        expect(error_state.exists()).toBe(true);
        expect(error_state.props("message")).toBe("Missing timeframe");
    });

    it.each([[403], [404]])("Displays an empty state for a %i", async (status) => {
        const recursive_get = jest.spyOn(tlp, "recursiveGet");
        mockFetchError(recursive_get, {
            status,
        });

        const wrapper = await mountComponent();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
    });

    it("Displays a generic error state for a 500", async () => {
        const recursive_get = jest.spyOn(tlp, "recursiveGet");
        mockFetchError(recursive_get, {
            status: 500,
            error_json: {
                error: {
                    message: "Internal Server Error",
                },
            },
        });

        const wrapper = await mountComponent();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(GanttBoard).exists()).toBe(false);

        const error_state = wrapper.findComponent(SomethingWentWrongEmptyState);
        expect(error_state.exists()).toBe(true);
        expect(error_state.props("message")).toBe("");
    });

    it("Displays a gantt board with tasks", async () => {
        const tasks = [
            { id: 1, start: new Date(2020, 3, 15), end: null },
            { id: 2, start: new Date(2020, 4, 15), end: null },
        ] as Task[];
        jest.spyOn(tlp, "recursiveGet").mockResolvedValue(tasks);

        const wrapper = await mountComponent();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);

        const gantt_board = wrapper.findComponent(GanttBoard);
        expect(gantt_board.exists()).toBe(true);
        expect(gantt_board.props("tasks")).toStrictEqual(tasks);
    });
});
