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
import CustomPropertyDate from "./CustomPropertyDate.vue";
import type { Property } from "../../../../../type";
import localVue from "../../../../../helpers/local-vue";
import DateFlatPicker from "../DateFlatPicker.vue";

describe("CustomPropertyDate", () => {
    function createWrapper(property: Property): Wrapper<CustomPropertyDate> {
        return shallowMount(CustomPropertyDate, {
            localVue,
            propsData: { currentlyUpdatedItemProperty: property },
        });
    }

    it(`does not render the component when type does not match`, () => {
        const currentlyUpdatedItemProperty = {
            value: "2019-06-30T00:00:00+03:00",
            is_required: true,
            name: "date field",
            type: "text",
        } as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        expect(wrapper.find("[data-test=document-custom-property-date]").exists()).toBeFalsy();
    });

    it(`User can choose a date value`, () => {
        const currentlyUpdatedItemProperty = {
            value: "2019-06-30T00:00:00+03:00",
            is_required: true,
            name: "date field",
            type: "date",
        } as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        wrapper.findComponent(DateFlatPicker).vm.$emit("input", "2019-06-30");

        expect(wrapper.emitted().input).toEqual([["2019-06-30"]]);
    });
});
