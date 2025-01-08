/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import TransitionDeleter from "./TransitionDeleter.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests.js";

describe("TransitionDeleter", () => {
    let transition, deleteTransition, is_transition_updated;

    beforeEach(() => {
        transition = {
            from_id: 18,
            to_id: 83,
        };
        deleteTransition = jest.fn();
        is_transition_updated = false;
    });

    const getWrapper = (
        is_operation_running,
        is_workflow_advanced_value,
        current_workflow_transitions_value,
    ) => {
        return shallowMount(TransitionDeleter, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        is_operation_running,
                    },
                    getters: {
                        current_workflow_transitions: () => current_workflow_transitions_value,
                        is_workflow_advanced: () => is_workflow_advanced_value,
                    },
                }),
            },
            propsData: {
                transition,
                deleteTransition,
                is_transition_updated,
            },
        });
    };

    it("given workflow is advanced, then confirmation is always needed", () => {
        const wrapper = getWrapper(false, true, []);

        expect(
            wrapper.find("[data-test=delete-transition-without-confirmation]").exists(),
        ).toBeFalsy();
    });

    it("given workflow is in simple mode and given there are many transition, then confirmation is NOT needed", () => {
        const transitions = [transition, { from_id: 35, to_id: 83 }];
        const wrapper = getWrapper(false, false, transitions);
        expect(
            wrapper.find("[data-test=delete-transition-without-confirmation]").exists(),
        ).toBeTruthy();
    });

    it("given workflow is in simple mode and given it's the last transition, then confirmation is needed", () => {
        const transitions = [transition];
        const wrapper = getWrapper(false, false, transitions);
        expect(
            wrapper.find("[data-test=delete-transition-without-confirmation]").exists(),
        ).toBeFalsy();
    });

    it("given there is no other operation running, then we can remove the transition", () => {
        const wrapper = getWrapper(false, false, []);
        wrapper.get("[data-test=delete-transition-without-confirmation]").trigger("click");
        expect(deleteTransition).toHaveBeenCalled();
    });

    it("given there is another operation running, then transition is not removed", () => {
        const wrapper = getWrapper(true, false, []);
        wrapper.get("[data-test=delete-transition-without-confirmation]").trigger("click");
        expect(deleteTransition).not.toHaveBeenCalled();
    });
});
