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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import localVue from "../../../../../helpers/local-vue";
import ObsolescenceDatePropertyForCreate from "./ObsolescenceDatePropertyForCreate.vue";
import moment from "moment/moment";
import emitter from "../../../../../helpers/emitter";

jest.mock("../../../../../helpers/emitter");

describe("ObsolescenceDatePropertyForCreate", () => {
    function createWrapper(
        value: string,
        is_obsolescence_date_property_used: boolean
    ): Wrapper<ObsolescenceDatePropertyForCreate> {
        return shallowMount(ObsolescenceDatePropertyForCreate, {
            localVue,
            propsData: { value },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { is_obsolescence_date_property_used },
                    },
                }),
            },
        });
    }

    function checkOptionValue(
        wrapper: Wrapper<ObsolescenceDatePropertyForCreate>,
        expected_value: string
    ): void {
        const checked_element = wrapper.find("option:checked").element;
        if (!(checked_element instanceof HTMLOptionElement)) {
            throw new Error("Can not find checked value");
        }
        expect(checked_element.value).toBe(expected_value);
    }

    describe("Component display", () => {
        it(`Displays the component if the obsolescence date property is used`, () => {
            const wrapper = createWrapper("", true);

            expect(wrapper.find("[data-test=obsolescence-date-property]").exists()).toBeTruthy();
        });
        it(`Does not display the component if the obsolescence date property is not used`, () => {
            const wrapper = createWrapper("", false);

            expect(wrapper.find("[data-test=obsolescence-date-property]").exists()).toBeFalsy();
        });
    });

    describe(`Should link flat picker and select helper`, () => {
        it(`Obsolescence date should be empty if the option "permanent" is chosen by the user`, async () => {
            const wrapper = createWrapper("", true);

            const select = wrapper.get("[data-test=document-obsolescence-date-select]");
            select.trigger("change");

            await wrapper.vm.$nextTick();

            checkOptionValue(wrapper, "permanent");
            expect(emitter.emit).toHaveBeenCalledWith("update-obsolescence-date-property", "");
        });
        it(`Obsolescence date should be the current day + 3 months if the option "3months" is chosen by the user`, () => {
            const wrapper = createWrapper("", true);

            const element = wrapper.findAll("option").at(1).element;

            if (!(element instanceof HTMLOptionElement)) {
                throw new Error("Can not select correct option element");
            }
            element.selected = true;

            wrapper.get("[data-test=document-obsolescence-date-select]").trigger("change");

            const current_date = moment().format("YYYY-MM-DD");

            const expected_date = moment(current_date, "YYYY-MM-DD")
                .add(3, "M")
                .format("YYYY-MM-DD");

            checkOptionValue(wrapper, "3");
            expect(emitter.emit).toHaveBeenCalledWith(
                "update-obsolescence-date-property",
                expected_date
            );
        });
    });
    describe(`Binding between the select box and the date input`, () => {
        it(`When the user click on the date input then the value of the select should be 'Fixed date`, async () => {
            const wrapper = createWrapper("", true);

            checkOptionValue(wrapper, "permanent");
            wrapper.find("[data-test=obsolescence-date-input]").vm.$emit("input", "2019-09-07");
            await wrapper.vm.$nextTick();

            expect(
                (
                    wrapper.find("[data-test=document-obsolescence-date-select]")
                        .element as HTMLSelectElement
                ).value
            ).toBe("fixed");
        });
    });
    describe(`Obsolescence date validity`, () => {
        it(`date is invalid when it's is anterior the current date`, async () => {
            const wrapper = createWrapper("", true);

            wrapper.find("[data-test=obsolescence-date-input]").vm.$emit("input", "2018-09-07");
            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=obsolescence-date-error-message]").exists()
            ).toBeTruthy();
        });
        it(`date is valid for today`, async () => {
            const wrapper = createWrapper("", true);

            wrapper
                .find("[data-test=obsolescence-date-input]")
                .vm.$emit("input", moment().format("YYYY-MM-DD"));
            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=obsolescence-date-error-message]").exists()
            ).toBeTruthy();
        });
        it(`date is valid when it's in the future`, async () => {
            const wrapper = createWrapper("", true);

            wrapper
                .find("[data-test=obsolescence-date-input]")
                .vm.$emit("input", moment().add(3, "day").format("YYYY-MM-DD"));
            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=obsolescence-date-error-message]").exists()
            ).toBeFalsy();
        });
    });
});
