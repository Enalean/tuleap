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

import TransitionMatrixColumnHeader from "./TransitionMatrixColumnHeader.vue";
import ConfigureStateButton from "./ConfigureStateButton.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests.js";

describe("TransitionMatrixColumnHeader", () => {
    let current_workflow_transitions_values,
        is_workflow_advanced_value = false;
    const getWrapper = () => {
        return shallowMount(TransitionMatrixColumnHeader, {
            global: {
                ...getGlobalTestOptions({
                    getters: {
                        current_workflow_transitions: () => current_workflow_transitions_values,
                        is_workflow_advanced: () => is_workflow_advanced_value,
                    },
                }),
            },
            propsData: {
                column: {
                    id: 476,
                    label: "On Going",
                },
            },
        });
    };

    describe("when the workflow is in simple mode", () => {
        const transition_from_new = {
            id: 663,
            from_id: null,
            to_id: 476,
        };
        const other_transition = {
            id: 575,
            from_id: 77,
            to_id: 476,
        };

        describe("and there is at least one transition not from 'New artifact'", () => {
            beforeEach(() => {
                current_workflow_transitions_values = [transition_from_new, other_transition];
            });
            it("shows the configure state button and passes that transition to it", () => {
                const wrapper = getWrapper();
                const button = wrapper.findComponent(ConfigureStateButton);

                expect(button.exists()).toBeTruthy();
                expect(button.props("transition")).toEqual(other_transition);
            });
        });

        describe("and the only transition is from 'New artifact'", () => {
            beforeEach(() => {
                current_workflow_transitions_values = [transition_from_new];
            });
            it("passes that transition to the button", () => {
                const wrapper = getWrapper();
                const button = wrapper.findComponent(ConfigureStateButton);

                expect(button.exists()).toBeTruthy();
                expect(button.props("transition")).toEqual(transition_from_new);
            });
        });

        it("and there is no transition to the given state, then it does not show the configure state button", () => {
            current_workflow_transitions_values = [];
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ConfigureStateButton).exists()).toBeFalsy();
        });
    });

    describe("when the workflow is in advanced mode", () => {
        beforeEach(() => {
            is_workflow_advanced_value = true;
        });
        it("does not show the configure state button", () => {
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ConfigureStateButton).exists()).toBeFalsy();
        });
    });
});
