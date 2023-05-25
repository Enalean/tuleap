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
 *
 */

import { shallowMount } from "@vue/test-utils";
import TransitionRulesEnforcementWarning from "./TransitionRulesEnforcementWarning.vue";
import { createLocalVueForTests } from "../support/local-vue.js";
import { create } from "../support/factories.js";
import store_options from "../store/index.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("TransitionRulesEnforcementWarning", () => {
    let store;

    beforeEach(() => {
        store = createStoreMock(store_options, {
            current_tracker: create("tracker", { workflow: create("workflow") }),
        });
    });

    const getWrapper = async () => {
        return shallowMount(TransitionRulesEnforcementWarning, {
            mocks: {
                $store: store,
            },
            localVue: await createLocalVueForTests(),
        });
    };

    afterEach(() => store.reset());

    describe("when rules enforcement is active", () => {
        beforeEach(() => (store.getters.are_transition_rules_enforced = true));

        it("shows only rules enforcement active message", async () => {
            const wrapper = await getWrapper();
            expect(
                wrapper.find('[data-test-message="rules-enforcement-active"]').exists()
            ).toBeTruthy();
            expect(
                wrapper.find('[data-test-message="rules-enforcement-inactive"]').exists()
            ).toBeFalsy();
        });
    });

    describe("when rules enforcement is inactive", () => {
        beforeEach(() => (store.getters.are_transition_rules_enforced = false));

        it("shows only rule enforcement inactive message", async () => {
            const wrapper = await getWrapper();
            expect(
                wrapper.find('[data-test-message="rules-enforcement-active"]').exists()
            ).toBeFalsy();
            expect(
                wrapper.find('[data-test-message="rules-enforcement-inactive"]').exists()
            ).toBeTruthy();
        });
    });
});
