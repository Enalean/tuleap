/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { mount } from "@vue/test-utils";

import FrozenFieldsAction from "./FrozenFieldsAction.vue";
import { create } from "../../../support/factories.js";
import * as list_picker from "@tuleap/list-picker";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests.js";

describe("FrozenFieldsAction", () => {
    const date_field_id = 43;
    const date_field = create("field", { field_id: date_field_id, type: "date" });
    const int_field_id = 44;
    const int_field = create("field", { field_id: int_field_id, type: "int" });
    const float_field_id = 45;
    const float_field = create("field", { field_id: float_field_id, type: "float" });
    const status_field_id = 46;
    const status_field = create("field", { field_id: status_field_id, type: "sb" });
    let post_actions_value;
    let current_tracker;
    let is_modal_save_running;

    beforeEach(() => {
        jest.spyOn(list_picker, "createListPicker").mockImplementation();

        current_tracker = {
            fields: [date_field, int_field, float_field, status_field],
        };

        post_actions_value = [];
        current_tracker = null;
        is_modal_save_running = false;
    });

    function instantiateComponent() {
        return mount(FrozenFieldsAction, {
            propsData: { post_action: create("post_action", "presented") },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_tracker,
                    },
                    getters: {
                        current_workflow_field: () => status_field,
                        is_workflow_advanced: () => false,
                    },
                    modules: {
                        transitionModal: {
                            state: {
                                current_transition: create("transition"),
                                is_modal_save_running,
                            },
                            getters: {
                                set_value_action_fields: () => [date_field, int_field, float_field],
                                post_actions: () => post_actions_value,
                                is_agile_dashboard_used: () => false,
                                is_program_management_used: () => false,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it("disables the option when no fields are available", () => {
        current_tracker = null;
        const wrapper = instantiateComponent();

        expect(wrapper.get("[data-test=freeze_fields]").attributes().disabled).toBe("");
    });

    it("disables the option when post-action is already used", () => {
        post_actions_value = [create("post_action", { type: "frozen_fields" })];
        const wrapper = instantiateComponent();

        expect(wrapper.get("[data-test=freeze_fields]").attributes().disabled).toBe("");
    });

    it("should not show the status field as available", () => {
        const wrapper = instantiateComponent();
        expect(wrapper.find(`[data-test=field_${status_field_id}]`).exists()).toBeFalsy();
    });

    it(`when the modal is saving, it will disable the fields select`, () => {
        is_modal_save_running = true;
        const wrapper = instantiateComponent();
        const fields_select = wrapper.get("[data-test=frozen-fields-selector]");
        expect(fields_select.attributes("disabled")).toBe("");
    });
});
