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
import ToBePlanned from "./ToBePlanned.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { Feature, State } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";
import { createConfigurationModule } from "../../../store/configuration";

jest.useFakeTimers();

const PROGRAM_ID = 202;
describe("ToBePlanned", () => {
    let retrieveSpy: jest.Mock;
    beforeEach(() => {
        retrieveSpy = jest.fn();
    });

    function getWrapper(
        features_in_store: Feature[],
    ): VueWrapper<InstanceType<typeof ToBePlanned>> {
        return shallowMount(ToBePlanned, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        to_be_planned_elements: features_in_store,
                    } as State,
                    actions: {
                        retrieveToBePlannedElement: (_state, program_id) => retrieveSpy(program_id),
                    },
                    modules: {
                        configuration: createConfigurationModule({
                            program_id: PROGRAM_ID,
                        } as ConfigurationState),
                    },
                }),
            },
        });
    }

    it("Displays the empty state when no artifact are found", async () => {
        const wrapper = getWrapper([]);
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
    });

    it("Displays an error when rest route fail", async () => {
        const wrapper = getWrapper([]);
        wrapper.vm.has_error = true;
        wrapper.vm.error_message = "Oups, something happened";
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(true);
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

        const wrapper = getWrapper([element_one, element_two]);
        await jest.runOnlyPendingTimersAsync();

        expect(retrieveSpy).toHaveBeenCalledWith(PROGRAM_ID);
        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
    });
});
