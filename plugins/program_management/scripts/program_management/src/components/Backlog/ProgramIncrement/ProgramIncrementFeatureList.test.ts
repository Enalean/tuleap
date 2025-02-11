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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ProgramIncrementFeatureList from "./ProgramIncrementFeatureList.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import type { Feature } from "../../../type";
import FeatureCard from "./FeatureCard.vue";

jest.useFakeTimers();

type CachedContentChecker = () => boolean;
type CachedContentGetter = () => Feature[];

describe("ProgramIncrementFeatureList", () => {
    let increment: ProgramIncrement;
    beforeEach(() => {
        increment = {
            id: 1,
            title: "PI 1",
            status: "On going",
            start_date: "2020 Feb 6",
            end_date: "2020 Feb 28",
            user_can_plan: true,
        } as ProgramIncrement;
    });

    function getWrapper(
        is_already_loaded: boolean,
        loaded_features: Feature[],
        remote_features: Feature[],
    ): VueWrapper<InstanceType<typeof ProgramIncrementFeatureList>> {
        return shallowMount(ProgramIncrementFeatureList, {
            global: {
                ...getGlobalTestOptions({
                    getters: {
                        isProgramIncrementAlreadyAdded: (): CachedContentChecker => () =>
                            is_already_loaded,
                        getFeaturesInProgramIncrement: (): CachedContentGetter => () =>
                            loaded_features,
                    },
                    actions: {
                        getFeatureAndStoreInProgramIncrement: () =>
                            Promise.resolve(remote_features),
                    },
                }),
            },
            props: { increment },
        });
    }

    it("Displays the empty state when no features are found", () => {
        const wrapper = getWrapper(true, [], []);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").attributes("data-can-plan"),
        ).toBe("true");
    });

    it("Displays an error when rest route fail", async () => {
        const wrapper = getWrapper(true, [], []);
        wrapper.vm.has_error = true;
        wrapper.vm.error_message = "Oups, something happened";
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

        const wrapper = getWrapper(true, [element_one, element_two], []);
        await jest.runOnlyPendingTimersAsync();

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

        const wrapper = getWrapper(false, [], [element_one, element_two]);
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.findAllComponents(FeatureCard)).toHaveLength(2);
    });

    it("Does not have the can-plan attribute when user can not plan elements", () => {
        increment.user_can_plan = false;

        const wrapper = getWrapper(true, [], []);

        expect(
            wrapper.get("[data-test=program-increment-feature-list]").attributes("data-can-plan"),
        ).toBe("false");
    });
});
