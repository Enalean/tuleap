/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import FirstConfigurationSections from "./FirstConfigurationSections.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests.js";

describe("FirstConfigurationSections", () => {
    const getWrapper = () => {
        return shallowMount(FirstConfigurationSections, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        is_operation_running: true,
                    },
                    getters: {
                        selectbox_fields: () => [],
                    },
                }),
            },
        });
    };

    const create_workflow_selector = '[data-test="create-workflow"]';

    describe("When an operation is running", () => {
        it("the submit button will be disabled", () => {
            const wrapper = getWrapper();
            const create_workflow_button = wrapper.get(create_workflow_selector);
            expect(create_workflow_button.attributes("disabled")).toBe("");
        });
    });
});
