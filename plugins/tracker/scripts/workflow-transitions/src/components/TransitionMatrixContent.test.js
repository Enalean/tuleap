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
import { createLocalVueForTests } from "../support/local-vue.js";
import store_options from "../store/index.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("TransitionMatrixContent", () => {
    let store, wrapper;

    const getWrapper = async () => {
        return shallowMount(TransitionMatrixContent, {
            mocks: {
                $store: store,
            },
            localVue: await createLocalVueForTests(),
            propsData: {
                from: create("field_value"),
                to: create("field_value"),
            },
        });
    };

    beforeEach(async () => {
        store = createStoreMock(store_options, { is_operation_running: false });
        store.getters.current_workflow_transitions = [];
        store.getters.is_workflow_advanced = true;
        wrapper = await getWrapper();
    });

    afterEach(() => {
        wrapper.destroy(); //Otherwise we get problems because current_workflow_transitions becomes a function
        store.reset();
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
                    store.state.is_operation_running = true;
                    await wrapper.vm.$nextTick();

                    expect(wrapper.get(create_transition_selector).classes()).toContain(
                        "tracker-workflow-transition-action-disabled"
                    );

                    wrapper.get(create_transition_selector).trigger("click");

                    expect(store.commit).not.toHaveBeenCalledWith("createTransition");
                });
            });

            describe("when user clicks to create transition", () => {
                let resolveCreateTransition;

                beforeEach(() => {
                    store.dispatch.mockReturnValue(
                        new Promise((resolve) => {
                            resolveCreateTransition = resolve;
                        })
                    );
                    wrapper.get(create_transition_selector).trigger("click");
                });

                it("shows a spinner", () => {
                    expect(wrapper.find(spinner_selector).exists()).toBeTruthy();
                });
                it("creates transition", () => {
                    expect(store.dispatch).toHaveBeenCalledWith("createTransition", {
                        from_id: 1,
                        to_id: 2,
                    });
                });

                describe("and new transition successfully saved", () => {
                    beforeEach(async () => {
                        resolveCreateTransition();
                        await wrapper.vm.$nextTick();
                    });

                    it("hides spinner", () => {
                        expect(wrapper.find(spinner_selector).exists()).toBeFalsy();
                    });
                });
            });
        });

        describe("with a transition", () => {
            const transition = {
                from_id: 1,
                to_id: 2,
                updated: false,
            };

            beforeEach(() => {
                store.getters.current_workflow_transitions = [transition];
            });

            it("shows transition", () => {
                expect(wrapper.findComponent(TransitionDeleter).exists()).toBeTruthy();
                expect(wrapper.find(transition_configuration_selector).exists()).toBeTruthy();
            });

            describe("and the workflow is in simple mode", () => {
                beforeEach(() => {
                    store.getters.is_workflow_advanced = false;
                });

                it("does not show the 'configure transition' button", () => {
                    expect(wrapper.find(transition_configuration_selector).exists()).toBeFalsy();
                });
            });

            describe("when the transition has just been updated", () => {
                beforeEach(() => {
                    transition.updated = true;
                });

                it("shows an 'updated' animation", () => {
                    const configure_transition_button = wrapper.get(
                        transition_configuration_selector
                    );

                    expect(configure_transition_button.classes()).toContain("tlp-button-success");
                    expect(wrapper.classes()).toContain(
                        "tracker-workflow-transition-action-updated"
                    );
                });
            });
        });
    });
});
