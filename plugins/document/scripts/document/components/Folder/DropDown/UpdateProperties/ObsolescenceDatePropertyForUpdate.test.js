/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import localVue from "../../../../helpers/local-vue";
import ObsolescenceDatePropertyForUpdate from "./ObsolescenceDatePropertyForUpdate.vue";
import moment from "moment/moment";
import DateFlatPicker from "../PropertiesForCreateOrUpdate/DateFlatPicker.vue";

describe("ObsolescenceDatePropertyForUpdate", () => {
    function createWrapper(is_obsolescence_date_property_used) {
        return shallowMount(ObsolescenceDatePropertyForUpdate, {
            localVue,
            propsData: { value: "" },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { is_obsolescence_date_property_used },
                    },
                }),
            },
        });
    }

    describe("Component display", () => {
        it(`Displays the component if the obsolescence date property is used`, () => {
            const wrapper = createWrapper(true);

            expect(wrapper.find("[data-test=obsolescence-date-property]").exists()).toBeTruthy();
        });
        it(`Does not display the component if the obsolescence date property is not used`, () => {
            const wrapper = createWrapper(false);

            expect(wrapper.find("[data-test=obsolescence-date-property]").exists()).toBeFalsy();
        });
    });
    describe(`Should link flat picker and select helper`, () => {
        it(`Obsolescence date should be null if the option "permanent" is chosen by the user`, () => {
            const wrapper = createWrapper(true);

            wrapper.get("[data-test=document-obsolescence-date-select-update]");

            expect(wrapper.vm.selected_value).toBe("permanent");
            expect(wrapper.vm.date_value).toBe("");
        });
        it(`Obsolescence date should be the current day + 3 months if the option "3months" is chosen by the user`, () => {
            const wrapper = createWrapper(true);

            wrapper.findAll("option").at(1).element.selected = true;

            wrapper.get("[data-test=document-obsolescence-date-select-update]").trigger("change");

            const current_date = moment().format("YYYY-MM-DD");

            const expected_date = moment(current_date, "YYYY-MM-DD")
                .add(3, "M")
                .format("YYYY-MM-DD");

            expect(wrapper.vm.selected_value).toBe("3");
            expect(wrapper.vm.date_value).toStrictEqual(expected_date);
        });
    });
    describe(`Binding between the select box and the date input`, () => {
        it(`When the user click on the date input then the value of the select should be 'Fixed date`, () => {
            const wrapper = createWrapper(true);

            expect(wrapper.vm.selected_value).toBe("permanent");
            wrapper.findComponent(DateFlatPicker).vm.$emit("input", "2019-06-30");

            expect(wrapper.vm.selected_value).toBe("fixed");
        });
    });
});
