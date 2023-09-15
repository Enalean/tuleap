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
import { create } from "../support/factories.js";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests.js";

describe("TransitionRulesEnforcementWarning", () => {
    const getWrapper = (are_transition_rules_enforced) => {
        return shallowMount(TransitionRulesEnforcementWarning, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_tracker: create("tracker", { workflow: create("workflow") }),
                    },
                    getters: {
                        are_transition_rules_enforced: () => are_transition_rules_enforced,
                        is_workflow_legacy: () => false,
                    },
                }),
            },
        });
    };

    describe("when rules enforcement is active", () => {
        it("shows only rules enforcement active message", () => {
            const wrapper = getWrapper(true);
            expect(
                wrapper.find('[data-test-message="rules-enforcement-active"]').exists(),
            ).toBeTruthy();
            expect(
                wrapper.find('[data-test-message="rules-enforcement-inactive"]').exists(),
            ).toBeFalsy();
        });
    });

    describe("when rules enforcement is inactive", () => {
        it("shows only rule enforcement inactive message", () => {
            const wrapper = getWrapper(false);
            expect(
                wrapper.find('[data-test-message="rules-enforcement-active"]').exists(),
            ).toBeFalsy();
            expect(
                wrapper.find('[data-test-message="rules-enforcement-inactive"]').exists(),
            ).toBeTruthy();
        });
    });
});
