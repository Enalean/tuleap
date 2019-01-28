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

import SetValueAction from "./SetValueAction.vue";
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import { create } from "../../support/factories";
import { DATE_FIELD } from "../../../../constants/fields-constants.js";

describe("SetValueAction", () => {
    let store;
    const set_date_value_action = create("post_action", "presented", {
        type: "set_field_value",
        field_type: "date",
        field_id: 43,
        value: "current"
    });
    const date_field = create("field", { field_id: 43, type: "date" });

    const set_int_value_action = create("post_action", "presented", {
        type: "set_field_value",
        field_type: "int",
        field_id: 44,
        value: 200
    });
    const int_field = create("field", { field_id: 44, type: "int" });

    const set_float_value_action = create("post_action", "presented", {
        type: "set_field_value",
        field_type: "float",
        field_id: 45,
        value: 12.34
    });
    const float_field = create("field", { field_id: 45, type: "float" });

    let wrapper;

    beforeEach(() => {
        const store_options = {
            state: {
                current_tracker: create("tracker", {
                    fields: [date_field, int_field, float_field]
                }),
                transitionModal: {
                    current_transition: create("transition"),
                    post_actions_by_unique_id: {
                        set_date_value_action_id: set_date_value_action,
                        set_int_value_action_id: set_int_value_action,
                        set_float_value_action_id: set_float_value_action
                    },
                    is_modal_save_running: false
                }
            }
        };

        store = createStoreMock(store_options);

        wrapper = shallowMount(SetValueAction, {
            mocks: { $store: store },
            propsData: { actionId: "set_date_value_action_id" },
            localVue
        });
    });

    afterEach(() => store.reset());

    const date_value_input_selector = '[data-test-type="date-value"]';
    const int_value_input_selector = '[data-test-type="int-value"]';
    const float_value_input_selector = '[data-test-type="float-value"]';

    it("Shows date field in date fields group", () => {
        let date_group_selector = `optgroup[data-test-type="${DATE_FIELD}-group"]`;
        let date_select_group = wrapper.find(date_group_selector);
        expect(date_select_group.contains('[data-test-type="field_43"]')).toBeTruthy();
    });

    describe("when post action sets a date field", () => {
        beforeEach(() => wrapper.setProps({ actionId: "set_date_value_action_id" }));

        it("select corresponding date field", () => {
            expect(wrapper.vm.post_action_field).toEqual(date_field);
        });

        it("shows post action value", () => {
            expect(wrapper.find(date_value_input_selector).element.value).toBe("current");
        });
    });

    describe("when post action sets an int field", () => {
        beforeEach(() => wrapper.setProps({ actionId: "set_int_value_action_id" }));

        it("shows value of action", () => {
            expect(wrapper.vm.post_action_field).toEqual(int_field);
        });

        it("shows value of action", () => {
            expect(wrapper.find(int_value_input_selector).element.value).toBe("200");
        });
    });

    describe("when post action sets a float field", () => {
        beforeEach(() => wrapper.setProps({ actionId: "set_float_value_action_id" }));

        it("shows value of action", () => {
            expect(wrapper.vm.post_action_field).toEqual(float_field);
        });

        it("shows value of action", () => {
            expect(wrapper.find(float_value_input_selector).element.value).toBe("12.34");
        });
    });
});
