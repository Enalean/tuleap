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
import ProgramIncrementFeatureList from "./ProgramIncrementFeatureList.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Feature } from "../../../type";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";

describe("ProgramIncrementFeatureList", () => {
    it("Displays the empty state when no features are found", async () => {
        const store: Store = createStoreMock({
            getters: {
                getFeaturesInProgramIncrement: jest.fn().mockReturnValue([]),
                isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
            },
            state: {
                configuration: { program_id: 202 },
            },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: false,
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: store,
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            (wrapper.get("[data-test=program-increment-feature-list]").element as HTMLElement)
                .dataset.canPlan,
        ).toBe("true");
    });

    it("Displays an error when rest route fail", async () => {
        const store: Store = createStoreMock({
            getters: {
                getFeaturesInProgramIncrement: jest.fn().mockReturnValue([]),
                isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
            },
            state: {
                configuration: { program_id: 202 },
            },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: true,
                    error_message: "Oups, something happened",
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: store,
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(true);
        expect(
            (wrapper.get("[data-test=program-increment-feature-list]").element as HTMLElement)
                .dataset.canPlan,
        ).toBe("true");
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
            getters: {
                getFeaturesInProgramIncrement: jest
                    .fn()
                    .mockReturnValue([element_one, element_two]),
                isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
            },
            state: {
                configuration: { program_id: 202 },
            },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([element_one, element_two]);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: false,
                    error_message: "",
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: store,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            (wrapper.get("[data-test=program-increment-feature-list]").element as HTMLElement)
                .dataset.canPlan,
        ).toBe("true");
    });

    it("Retrieve elements to display and store them in store", async () => {
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
            getters: {
                getFeaturesInProgramIncrement: jest
                    .fn()
                    .mockReturnValue([element_one, element_two]),
                isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(false),
            },
            state: {
                configuration: { program_id: 202 },
            },
        });
        jest.spyOn(store, "dispatch").mockResolvedValue([element_one, element_two]);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: store,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "getFeatureAndStoreInProgramIncrement",
            {
                id: 1,
                title: "PI 1",
                status: "On going",
                start_date: "2020 Feb 6",
                end_date: "2020 Feb 28",
                user_can_plan: true,
            },
        );
    });

    it("Does not have the can-plan attribute when user can not plan elements", async () => {
        const store: Store = createStoreMock({
            getters: {
                getFeaturesInProgramIncrement: jest.fn().mockReturnValue([]),
                isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
            },
            state: {
                configuration: { program_id: 202 },
            },
        });

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: false,
                    error_message: "",
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: false,
                } as ProgramIncrement,
            },
            mocks: {
                $store: createStoreMock({
                    getters: {
                        getFeaturesInProgramIncrement: jest.fn().mockReturnValue([]),
                        isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
                    },
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        expect(
            (wrapper.get("[data-test=program-increment-feature-list]").element as HTMLElement)
                .dataset.canPlan,
        ).toBeUndefined();
    });
});
