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
import { createLocalVueForTests } from "../support/local-vue.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("TransitionDeleter", () => {
    let store, transition, deleteTransition, is_transition_updated;

    beforeEach(() => {
        transition = {
            from_id: 18,
            to_id: 83,
        };
        deleteTransition = jest.fn();
        is_transition_updated = false;
    });

    const getWrapper = async (
        is_operation_running,
        is_workflow_advanced,
        current_workflow_transitions,
    ) => {
        const store_options = {
            state: { is_operation_running },
            getters: { current_workflow_transitions, is_workflow_advanced },
        };

        store = createStoreMock(store_options, {});

        return shallowMount(TransitionDeleter, {
            mocks: {
                $store: store,
            },
            localVue: await createLocalVueForTests(),
            propsData: {
                transition,
                deleteTransition,
                is_transition_updated,
            },
        });
    };

    afterEach(() => store.reset());

    it("given workflow is advanced, then confirmation is always needed", async () => {
        const wrapper = await getWrapper(false, true, []);

        expect(
            wrapper.find("[data-test=delete-transition-without-confirmation]").exists(),
        ).toBeFalsy();
    });

    it("given workflow is in simple mode and given there are many transition, then confirmation is NOT needed", async () => {
        const transitions = [transition, { from_id: 35, to_id: 83 }];
        const wrapper = await getWrapper(false, false, transitions);
        expect(
            wrapper.find("[data-test=delete-transition-without-confirmation]").exists(),
        ).toBeTruthy();
    });

    it("given workflow is in simple mode and given it's the last transition, then confirmation is needed", async () => {
        const transitions = [transition];
        const wrapper = await getWrapper(false, false, transitions);
        expect(
            wrapper.find("[data-test=delete-transition-without-confirmation]").exists(),
        ).toBeFalsy();
    });

    it("given there is no other operation running, then we can remove the transition", async () => {
        const wrapper = await getWrapper(false, false, []);
        wrapper.get("[data-test=delete-transition-without-confirmation]").trigger("click");
        expect(deleteTransition).toHaveBeenCalled();
    });

    it("given there is another operation running, then transition is not removed", async () => {
        const wrapper = await getWrapper(true, false, []);
        wrapper.get("[data-test=delete-transition-without-confirmation]").trigger("click");
        expect(deleteTransition).not.toHaveBeenCalled();
    });
});
