/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

import Vuex from "vuex";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import TransitionMatrixContent from "./TransitionMatrixContent.vue";

const localVue = createLocalVue();
localVue.use(Vuex);

describe("TransitionMatrixContent", () => {
    let store_state;
    let store_actions;
    let wrapper;

    beforeEach(() => {
        store_state = {
            is_operation_running: false
        };
        store_actions = {
            createTransition: jasmine.createSpy("createTransition")
        };
        const store = new Vuex.Store({
            state: store_state,
            actions: store_actions
        });
        wrapper = shallowMount(TransitionMatrixContent, {
            store,
            localVue,
            propsData: {
                from: { id: 1, label: "first value" },
                to: { id: 1, label: "second value" },
                transition: null
            }
        });
    });

    const create_transition_selector = '[data-test-action="create-transition"]';
    const forbidden_selector = '[data-test-type="forbidden-transition"]';
    const spinner_selector = '[data-test-type="spinner"]';
    const transition_mark_selector = '[data-test-type="transition-mark"]';
    const transition_configuration_selector = '[data-test-action="configure-transition"]';

    describe("when from equals to", () => {
        beforeEach(() => {
            const from = { id: 1, label: "first value" };
            wrapper.setProps({ from, to: from });
        });

        it("transition creation is not possible", () => {
            expect(wrapper.contains(create_transition_selector)).toBeFalsy();
        });
        it("no operation is possible", () => {
            expect(wrapper.contains(forbidden_selector)).toBeTruthy();
        });
    });

    describe("when from and to differs", () => {
        beforeEach(() => {
            wrapper.setProps({
                from: { id: 1, label: "first value" },
                to: { id: 2, label: "second value" }
            });
        });

        describe("without any transition", () => {
            beforeEach(() => {
                wrapper.setProps({ transition: null });
            });

            it("transition creation is possible", () => {
                expect(wrapper.contains(create_transition_selector)).toBeTruthy();
            });

            describe("during another operation running", () => {
                beforeEach(() => {
                    store_state.is_operation_running = true;
                });

                it("transition creation is disabled", () => {
                    expect(wrapper.find(create_transition_selector).classes()).toContain(
                        "tracker-workflow-transition-action-disabled"
                    );
                });

                describe("when user clicks to create transition", () => {
                    beforeEach(() => {
                        wrapper.find(create_transition_selector).trigger("click");
                    });

                    it("does nothing", () => {
                        expect(store_actions.createTransition).not.toHaveBeenCalled();
                    });
                });
            });

            describe("when user clicks to create transition", () => {
                let resolveCreateTransition;

                beforeEach(() => {
                    store_actions.createTransition.and.callFake(
                        () =>
                            new Promise(resolve => {
                                resolveCreateTransition = resolve;
                            })
                    );
                    wrapper.find(create_transition_selector).trigger("click");
                });

                it("shows a spinner", () => {
                    expect(wrapper.contains(spinner_selector)).toBeTruthy();
                });
                it("saves a new transition", () => {
                    expect(store_actions.createTransition).toHaveBeenCalled();
                    expect(store_actions.createTransition.calls.mostRecent().args[1]).toEqual(
                        jasmine.objectContaining({
                            from_id: 1,
                            to_id: 2
                        })
                    );
                });

                describe("and new transition successfully saved", () => {
                    beforeEach(async () => {
                        resolveCreateTransition();
                        await wrapper.vm.$nextTick();
                    });

                    it("hides spinner", () => {
                        expect(wrapper.contains(spinner_selector)).toBeFalsy();
                    });
                });
            });
        });

        describe("with a transition", () => {
            beforeEach(() => {
                wrapper.setProps({ transition: {} });
            });

            it("shows transition", () => {
                expect(wrapper.contains(transition_mark_selector)).toBeTruthy();
                expect(wrapper.contains(transition_configuration_selector)).toBeTruthy();
            });
        });
    });
});
