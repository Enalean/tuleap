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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import ProgramIncrementFeatureList from "./ProgramIncrementFeatureList.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import type { Feature } from "../../../type";

describe("ProgramIncrementFeatureList", () => {
    let store: Store, increment: ProgramIncrement;
    beforeEach(() => {
        store = createStoreMock({
            state: {},
            getters: {
                getFeaturesInProgramIncrement: () => [],
                isProgramIncrementAlreadyAdded: () => true,
            },
        });
        increment = {
            id: 1,
            title: "PI 1",
            status: "On going",
            start_date: "2020 Feb 6",
            end_date: "2020 Feb 28",
            user_can_plan: true,
        } as ProgramIncrement;
    });

    async function getWrapper(): Promise<Wrapper<Vue>> {
        return shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            propsData: { increment },
            mocks: { $store: store },
        });
    }

    it("Displays the empty state when no features are found", async () => {
        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").attributes("data-can-plan"),
        ).toBe("true");
    });

    it("Displays an error when rest route fail", async () => {
        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = await getWrapper();
        wrapper.setData({ has_error: true, error_message: "Oups, something happened" });
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(true);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").attributes("data-can-plan"),
        ).toBe("true");
    });

    it("Displays the elements to be planned", async () => {
        const element_one = {
            id: 1,
            title: "My artifact",
            tracker: { label: "bug" },
        } as Feature;
        const element_two = {
            id: 2,
            title: "My user story",
            tracker: { label: "user_stories" },
        } as Feature;

        store = createStoreMock({
            state: {},
            getters: {
                getFeaturesInProgramIncrement: () => [element_one, element_two],
                isProgramIncrementAlreadyAdded: () => true,
            },
        });

        const wrapper = await getWrapper();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").attributes("data-can-plan"),
        ).toBe("true");
    });

    it("Retrieve elements to display and store them in store", async () => {
        jest.useFakeTimers();
        const element_one = {
            id: 1,
            title: "My artifact",
            tracker: { label: "bug" },
        } as Feature;
        const element_two = {
            id: 2,
            title: "My user story",
            tracker: { label: "user_stories" },
        } as Feature;

        store = createStoreMock({
            state: {},
            getters: {
                getFeaturesInProgramIncrement: () => [element_one, element_two],
                isProgramIncrementAlreadyAdded: () => false,
            },
        });
        jest.spyOn(store, "dispatch").mockResolvedValue([element_one, element_two]);

        const wrapper = await getWrapper();
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "getFeatureAndStoreInProgramIncrement",
            increment,
        );
    });

    it("Does not have the can-plan attribute when user can not plan elements", async () => {
        increment.user_can_plan = false;
        jest.spyOn(store, "dispatch").mockResolvedValue([]);

        const wrapper = await getWrapper();

        expect(
            wrapper.get("[data-test=program-increment-feature-list]").attributes("data-can-plan"),
        ).toBeUndefined();
    });
});
