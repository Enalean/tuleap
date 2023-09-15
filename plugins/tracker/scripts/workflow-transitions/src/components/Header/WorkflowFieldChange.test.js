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
import WorkflowFieldChange from "./WorkflowFieldChange.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests.js";

describe(`WorkflowFieldChange`, () => {
    function createWrapper(is_operation_running) {
        return shallowMount(WorkflowFieldChange, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        is_operation_running,
                    },
                    getters: {
                        workflow_field_label: () => "Status",
                        current_tracker_id: () => 145,
                    },
                }),
            },
        });
    }
    describe(`when an operation is running`, () => {
        it(`will disable the "Change or remove" button`, () => {
            const wrapper = createWrapper(true);
            const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
            expect(change_remove_button.attributes("disabled")).toBe("");
        });
    });

    it(`when I click the "Change or remove" button, it will open a confirmation modal`, async () => {
        const wrapper = createWrapper(false);

        const change_remove_button = wrapper.get("[data-test=change-or-remove-button]");
        await change_remove_button.trigger("click");

        expect(wrapper.find("[data-test=change-field-confirmation-modal]").exists()).toBe(true);
    });
});
