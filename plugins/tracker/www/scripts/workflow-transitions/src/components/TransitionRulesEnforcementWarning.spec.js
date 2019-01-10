/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import localVue from "../support/local-vue.js";
import { create } from "../support/factories.js";
import store_options from "../store/index.js";
import { createStoreWrapper } from "../support/store-wrapper.spec-helper.js";

describe("TransitionRulesEnforcementWarning", () => {
    let store_wrapper;
    let wrapper;

    beforeEach(() => {
        store_wrapper = createStoreWrapper(store_options, {
            current_tracker: create("tracker", { workflow: create("workflow") })
        });
        wrapper = shallowMount(TransitionRulesEnforcementWarning, {
            store: store_wrapper.store,
            localVue
        });
    });

    const enforcement_active_message_selector = '[data-test-message="rules-enforcement-active"]';
    const enforcement_inactive_message_selector =
        '[data-test-message="rules-enforcement-inactive"]';

    describe("when rules enforcement is active", () => {
        beforeEach(() => (store_wrapper.state.current_tracker.workflow.is_used = 1));

        it("shows only rules enforcement active message", () => {
            expect(wrapper.contains(enforcement_active_message_selector)).toBeTruthy();
            expect(wrapper.contains(enforcement_inactive_message_selector)).toBeFalsy();
        });
    });

    describe("when rules enforcement is inactive", () => {
        beforeEach(() => (store_wrapper.state.current_tracker.workflow.is_used = 0));

        it("shows only rule enforcement inactive message", () => {
            expect(wrapper.contains(enforcement_active_message_selector)).toBeFalsy();
            expect(wrapper.contains(enforcement_inactive_message_selector)).toBeTruthy();
        });
    });
});
