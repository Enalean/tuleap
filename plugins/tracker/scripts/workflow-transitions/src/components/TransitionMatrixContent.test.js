/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import TransitionMatrixContent from "./TransitionMatrixContent.vue";
import TransitionDeleter from "./TransitionDeleter.vue";
import { create } from "../support/factories.js";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests.js";

jest.useFakeTimers();

describe("TransitionMatrixContent", () => {
    let wrapper;
    const createTransitionMock = jest.fn();

    const getWrapper = (
        is_operation_running,
        current_workflow_transitions_value,
        is_workflow_advanced_value,
    ) => {
        return shallowMount(TransitionMatrixContent, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        is_operation_running,
                    },
                    getters: {
                        current_workflow_transitions: () => current_workflow_transitions_value,
                        is_workflow_advanced: () => is_workflow_advanced_value,
                    },
                    actions: {
                        createTransition: createTransitionMock,
                    },
                }),
            },
            propsData: {
                from: { id: 1 },
                to: { id: 2 },
            },
        });
    };

    beforeEach(() => {
        wrapper = getWrapper(true, [], true);
    });

    const create_transition_selector = '[data-test-action="create-transition"]';
    const forbidden_selector = '[data-test-type="forbidden-transition"]';
    const spinner_selector = '[data-test-type="spinner"]';
    const transition_configuration_selector = '[data-test-action="configure-transition"]';

    describe("when from equals to", () => {
        beforeEach(() => {
            const from = create("field_value");
            wrapper.setProps({ from, to: from });
        });

        it("transition creation is not possible", () => {
            expect(wrapper.find(create_transition_selector).exists()).toBeFalsy();
        });
        it("no operation is possible", () => {
            expect(wrapper.find(forbidden_selector).exists()).toBeTruthy();
        });
    });

    describe("when from and to differs", () => {
        beforeEach(() => {
            wrapper.setProps({
                from: create("field_value", { id: 1, label: "first value" }),
                to: create("field_value", { id: 2, label: "second value" }),
            });
        });

        describe("without any transition", () => {
            it("transition creation is possible", () => {
                expect(wrapper.find(create_transition_selector).exists()).toBeTruthy();
            });

            describe("during another operation running", () => {
                it("transition creation is disabled", async () => {
                    await jest.runOnlyPendingTimersAsync();

                    expect(wrapper.get(create_transition_selector).classes()).toContain(
                        "tracker-workflow-transition-action-disabled",
                    );

                    wrapper.get(create_transition_selector).trigger("click");

                    expect(createTransitionMock).not.toHaveBeenCalled();
                });
            });

            it("user can create transition", async () => {
                wrapper = getWrapper(false, [], true);
                wrapper.get(create_transition_selector).trigger("click");
                await wrapper.vm.$nextTick();
                expect(wrapper.find(spinner_selector).exists()).toBeTruthy();

                expect(createTransitionMock).toHaveBeenCalled();
                await jest.runOnlyPendingTimersAsync();
                expect(wrapper.find(spinner_selector).exists()).toBeFalsy();
            });
        });

        describe("with a transition", () => {
            const transition = {
                from_id: 1,
                to_id: 2,
                updated: false,
            };

            it("shows transition", () => {
                wrapper = getWrapper(false, [transition], true);
                expect(wrapper.findComponent(TransitionDeleter).exists()).toBeTruthy();
                expect(wrapper.find(transition_configuration_selector).exists()).toBeTruthy();
            });

            it("does not show the 'configure transition' button when the workflow is in simple mode", () => {
                wrapper = getWrapper(false, [transition], false);
                expect(wrapper.find(transition_configuration_selector).exists()).toBeFalsy();
            });

            it("shows an 'updated' animation when the transition has just been updated", () => {
                const transition = {
                    from_id: 1,
                    to_id: 2,
                    updated: true,
                };

                wrapper = getWrapper(false, [transition], true);
                const configure_transition_button = wrapper.get(transition_configuration_selector);

                expect(configure_transition_button.classes()).toContain("tlp-button-success");
                expect(wrapper.classes()).toContain("tracker-workflow-transition-action-updated");
            });
        });
    });
});
