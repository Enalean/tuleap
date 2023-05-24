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
import DateInput from "./DateInput.vue";
import FloatInput from "./FloatInput.vue";
import IntInput from "./IntInput.vue";
import { createLocalVueForTests } from "../../../support/local-vue.js";
import { create } from "../../../support/factories.js";
import { DATE_FIELD } from "@tuleap/plugin-tracker-constants";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("SetValueAction", () => {
    let store;
    const date_field_id = 43;
    const date_field = create("field", { field_id: date_field_id, type: "date" });
    const int_field_id = 44;
    const int_field = create("field", { field_id: int_field_id, type: "int" });
    const float_field_id = 45;
    const float_field = create("field", { field_id: float_field_id, type: "float" });

    let wrapper;

    beforeEach(async () => {
        const current_tracker = {
            fields: [date_field, int_field, float_field],
        };

        const store_options = {
            state: {
                transitionModal: {
                    current_transition: create("transition"),
                    is_modal_save_running: false,
                },
                current_tracker: current_tracker,
            },
            getters: {
                "transitionModal/set_value_action_fields": [date_field, int_field, float_field],
                "transitionModal/post_actions": [],
                current_workflow_field: create("field", { field_id: 455, type: "sb" }),
                is_workflow_advanced: false,
                "transitionModal/is_agile_dashboard_used": false,
                "transitionModal/is_program_management_used": false,
            },
        };

        store = createStoreMock(store_options);

        wrapper = mount(SetValueAction, {
            mocks: { $store: store },
            propsData: { post_action: create("post_action", "presented") },
            localVue: await createLocalVueForTests(),
        });
    });

    afterEach(() => store.reset());

    it("Shows date field in date fields group", () => {
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
            store.getters["transitionModal/set_value_action_fields"] = [
                used_date_field,
                int_field,
                float_field,
            ];
        });

        it("shows a disabled option", () => {
            const date_field_option = wrapper.get('[data-test-type="field_43"]');
            expect(date_field_option.attributes().disabled).toBeTruthy();
        });
    });

    describe("when there are no valid fields", () => {
        it("disables the option", async () => {
            store.getters["transitionModal/set_value_action_fields"] = [];
            await wrapper.vm.$nextTick();

            expect(wrapper.get("[data-test=set_field]").attributes("disabled")).toBeTruthy();
        });
    });

    describe("when post action sets a date field", () => {
        const post_action = create("post_action", "presented", {
            type: "set_field_value",
            field_type: "date",
            field_id: date_field_id,
            value: "current",
        });
        beforeEach(() => wrapper.setProps({ post_action }));

        it("select corresponding date field", () => {
            expect(wrapper.vm.post_action_field).toBe(date_field);
        });

        it("shows post action value", () => {
            expect(wrapper.findComponent(DateInput).props().value).toBe("current");
        });
    });

    describe("when post action sets an int field", () => {
        const post_action = create("post_action", "presented", {
            type: "set_field_value",
            field_type: "int",
            field_id: int_field_id,
            value: 200,
        });
        beforeEach(() => wrapper.setProps({ post_action }));

        it("shows value of action", () => {
            expect(wrapper.vm.post_action_field).toBe(int_field);
            expect(wrapper.findComponent(IntInput).props().value).toBe(200);
        });
    });

    describe("when post action sets a float field", () => {
        const post_action = create("post_action", "presented", {
            type: "set_field_value",
            field_type: "float",
            field_id: float_field_id,
            value: 12.34,
        });
        beforeEach(() => wrapper.setProps({ post_action }));

        it("shows value of action", () => {
            expect(wrapper.vm.post_action_field).toBe(float_field);
            expect(wrapper.findComponent(FloatInput).props().value).toBe(12.34);
        });
    });
});
