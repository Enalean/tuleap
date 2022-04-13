/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import ToBePlanned from "./ToBePlanned.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Feature } from "../../../type";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";

describe("ToBePlanned", () => {
    it("Displays the empty state when no artifact are found", async () => {
        const store: Store = createStoreMock({
            state: { to_be_planned_elements: [], configuration: { program_id: 202 } },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    is_loading: false,
                    has_error: false,
                };
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
    });

    it("Displays an error when rest route fail", async () => {
        const store: Store = createStoreMock({
            state: { to_be_planned_elements: [], configuration: { program_id: 202 } },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    is_loading: false,
                    has_error: true,
                    error_message: "Oups, something happened",
                };
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(true);
    });

    it("Displays the elements to be planned", async () => {
        const element_one = {
            id: 1,
            title: "My artifact",
            tracker: {
                label: "bug",
            },
        } as Feature;
        const element_two = {
            id: 2,
            title: "My user story",
            tracker: {
                label: "user_stories",
            },
        } as Feature;

        const store: Store = createStoreMock({
            state: {
                to_be_planned_elements: [element_one, element_two],
                configuration: { program_id: 202 },
            },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([element_one, element_two]);

        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    is_loading: false,
                    has_error: false,
                    error_message: "",
                };
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
    });

    it("During loading, Then elements are retrieved and stored in store", async () => {
        const element_one = {
            id: 1,
            title: "My artifact",
            tracker: {
                label: "bug",
            },
        } as Feature;
        const element_two = {
            id: 2,
            title: "My user story",
            tracker: {
                label: "user_stories",
            },
        } as Feature;

        const store: Store = createStoreMock({
            state: { to_be_planned_elements: [], configuration: { program_id: 202 } },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([element_one, element_two]);

        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("retrieveToBePlannedElement", 202);
    });
});
