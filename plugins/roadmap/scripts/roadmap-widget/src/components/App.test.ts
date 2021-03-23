/*
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
import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import NoDataToShowEmptyState from "./NoDataToShowEmptyState.vue";
import * as tlp from "tlp";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import SomethingWentWrongEmptyState from "./SomethingWentWrongEmptyState.vue";

jest.mock("tlp");

describe("App", () => {
    it("Displays an empty state", async () => {
        jest.spyOn(tlp, "recursiveGet").mockResolvedValue([]);

        const wrapper = shallowMount(App, {
            propsData: {
                roadmap_id: 123,
            },
            localVue: await createRoadmapLocalVue(),
        });

        // wait for load & parse response
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);
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

        const wrapper = shallowMount(App, {
            propsData: {
                roadmap_id: 123,
            },
            localVue: await createRoadmapLocalVue(),
        });

        // wait for load & parse response
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);

        const error_state = wrapper.findComponent(SomethingWentWrongEmptyState);
        expect(error_state.exists()).toBe(true);
        expect(error_state.props("message")).toBe("Missing timeframe");
    });

    it.each([[403], [404]])("Displays an empty state for a %i", async (status) => {
        const recursive_get = jest.spyOn(tlp, "recursiveGet");
        mockFetchError(recursive_get, {
            status,
        });

        const wrapper = shallowMount(App, {
            propsData: {
                roadmap_id: 123,
            },
            localVue: await createRoadmapLocalVue(),
        });

        // wait for load & parse response
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(SomethingWentWrongEmptyState).exists()).toBe(false);
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

        const wrapper = shallowMount(App, {
            propsData: {
                roadmap_id: 123,
            },
            localVue: await createRoadmapLocalVue(),
        });

        // wait for load & parse response
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(NoDataToShowEmptyState).exists()).toBe(false);

        const error_state = wrapper.findComponent(SomethingWentWrongEmptyState);
        expect(error_state.exists()).toBe(true);
        expect(error_state.props("message")).toBe("");
    });
});
