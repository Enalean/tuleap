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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ObsolescenceDatePropertyForUpdate from "./ObsolescenceDatePropertyForUpdate.vue";
import moment from "moment/moment";
import DateFlatPicker from "../PropertiesForCreateOrUpdate/DateFlatPicker.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { ConfigurationState } from "../../../../store/configuration";
import { nextTick } from "vue";

function checkSelectedDateIsCorrect(
    wrapper: VueWrapper<InstanceType<typeof ObsolescenceDatePropertyForUpdate>>,
    expected_value: string,
): void {
    const select = wrapper.get("[data-test=document-obsolescence-date-select-update]");
    if (!(select.element instanceof HTMLSelectElement)) {
        throw new Error("Select for obsolescence date is not found");
    }
    expect(select.element.value).toBe(expected_value);
}

function checkDatePickerValueIsCorrect(
    wrapper: VueWrapper<InstanceType<typeof ObsolescenceDatePropertyForUpdate>>,
    expected_value: string,
): void {
    const date_picker = wrapper.findComponent(DateFlatPicker);

    expect(date_picker.vm.$props.value).toBe(expected_value);
}

describe("ObsolescenceDatePropertyForUpdate", () => {
    function createWrapper(
        is_obsolescence_date_property_used: boolean,
    ): VueWrapper<InstanceType<typeof ObsolescenceDatePropertyForUpdate>> {
        return shallowMount(ObsolescenceDatePropertyForUpdate, {
            props: { value: "" },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                is_obsolescence_date_property_used,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
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
        it(`Obsolescence date should be null if the option "permanent" is chosen by the user`, async () => {
            const wrapper = createWrapper(true);

            await nextTick();

            expect(wrapper.find("[data-test=obsolescence-date-property]").exists()).toBeTruthy();
            checkSelectedDateIsCorrect(wrapper, "permanent");
            checkDatePickerValueIsCorrect(wrapper, "");
        });
        it(`Obsolescence date should be the current day + 3 months if the option "3months" is chosen by the user`, async () => {
            const wrapper = createWrapper(true);

            const element = wrapper.findAll("option").at(1).element;
            if (!(element instanceof HTMLOptionElement)) {
                throw new Error("Can not select option");
            }
            element.selected = true;

            wrapper.get("[data-test=document-obsolescence-date-select-update]").trigger("change");
            await nextTick();

            const current_date = moment().format("YYYY-MM-DD");

            const expected_date = moment(current_date, "YYYY-MM-DD")
                .add(3, "M")
                .format("YYYY-MM-DD");

            expect(wrapper.find("[data-test=obsolescence-date-property]").exists()).toBeTruthy();
            checkSelectedDateIsCorrect(wrapper, "3");
            checkDatePickerValueIsCorrect(wrapper, expected_date);
        });
    });
    describe(`Binding between the select box and the date input`, () => {
        it(`When the user click on the date input then the value of the select should be 'Fixed date`, async () => {
            const wrapper = createWrapper(true);

            wrapper.findComponent(DateFlatPicker).vm.$emit("input", "2019-06-30");
            await nextTick();

            expect(wrapper.find("[data-test=obsolescence-date-property]").exists()).toBeTruthy();
            checkSelectedDateIsCorrect(wrapper, "fixed");
        });
    });
});
