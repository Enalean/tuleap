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

import { mount } from "@vue/test-utils";

import SetValueAction from "./SetValueAction.vue";
import { create } from "../../../support/factories.js";
import { DATE_FIELD } from "@tuleap/plugin-tracker-constants";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests.js";
import DateInput from "./DateInput.vue";

describe("SetValueAction", () => {
    let store;
    const date_field_id = 43;
    const date_field = create("field", { field_id: date_field_id, type: "date" });
    const int_field_id = 44;
    const int_field = create("field", { field_id: int_field_id, type: "int" });
    const float_field_id = 45;
    const float_field = create("field", { field_id: float_field_id, type: "float" });

    let current_tracker;
    let set_value_action_fields_value;

    beforeEach(() => {
        current_tracker = {
            fields: [date_field, int_field, float_field],
        };
        set_value_action_fields_value = [date_field, int_field, float_field];
    });

    function instantiateComponent(post_action) {
        return mount(SetValueAction, {
            mocks: { $store: store },
            propsData: { post_action },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_tracker,
                    },
                    getters: {
                        current_transition: () => create("transition"),
                        is_modal_save_running: () => false,
                        current_workflow_field: () =>
                            create("field", { field_id: 455, type: "sb" }),
                        is_workflow_advanced: () => false,
                    },
                    modules: {
                        transitionModal: {
                            getters: {
                                set_value_action_fields: () => set_value_action_fields_value,
                                post_actions: () => [],
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

    it("Shows date field in date fields group", () => {
        const wrapper = instantiateComponent(create("post_action", "presented"));
        const date_group_selector = `optgroup[data-test-type="${DATE_FIELD}-group"]`;
        const date_select_group = wrapper.get(date_group_selector);
        expect(date_select_group.find('[data-test-type="field_43"]').exists()).toBeTruthy();
    });

    describe("when fields are already used in other post actions", () => {
        beforeEach(() => {
            const used_date_field = {
                ...date_field,
                disabled: true,
            };
            set_value_action_fields_value = [used_date_field, int_field, float_field];
        });

        it("shows a disabled option", () => {
            const wrapper = instantiateComponent(create("post_action", "presented"));
            const date_field_option = wrapper.get('[data-test-type="field_43"]');
            expect(date_field_option.attributes().disabled).toBe("");
        });
    });

    describe("when there are no valid fields", () => {
        it("disables the option", () => {
            set_value_action_fields_value = [];
            const wrapper = instantiateComponent(create("post_action", "presented"));

            expect(wrapper.get("[data-test=set_field]").attributes("disabled")).toBe("");
        });
    });

    describe("when post action sets a date field", () => {
        const post_action = create("post_action", "presented", {
            type: "set_field_value",
            field_type: "date",
            field_id: date_field_id,
            value: "current",
        });

        it("select corresponding date field", () => {
            const wrapper = instantiateComponent(post_action);
            expect(wrapper.vm.post_action_field).toBe(date_field);
        });

        it("shows post action value", () => {
            const wrapper = instantiateComponent(post_action);
            expect(wrapper.findComponent(DateInput).props().input_value).toBe("current");
            expect(wrapper.vm.value).toBe("current");
        });
    });

    describe("when post action sets an int field", () => {
        const post_action = create("post_action", "presented", {
            type: "set_field_value",
            field_type: "int",
            field_id: int_field_id,
            value: 200,
        });

        it("shows value of action", () => {
            const wrapper = instantiateComponent(post_action);
            expect(wrapper.vm.post_action_field).toBe(int_field);
            expect(wrapper.vm.value).toBe(200);
        });
    });

    describe("when post action sets a float field", () => {
        const post_action = create("post_action", "presented", {
            type: "set_field_value",
            field_type: "float",
            field_id: float_field_id,
            value: 12.34,
        });

        it("shows value of action", () => {
            const wrapper = instantiateComponent(post_action);
            expect(wrapper.vm.post_action_field).toBe(float_field);
            expect(wrapper.vm.value).toBe(12.34);
        });
    });
});
