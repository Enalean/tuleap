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

import localVue from "../../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import CustomMetadataDate from "./CustomMetadataDate.vue";

describe("CustomMetadataDate", () => {
    let factory;
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(CustomMetadataDate, {
                localVue,
                propsData: { ...props }
            });
        };
    });
    it(`Given value is null
        Then it renders an input without bound value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: null,
            is_required: false,
            name: "date field",
            type: "date"
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });
        const date_input = wrapper.find("[data-test=document-date-input]");

        expect(date_input.element.value).toEqual("");
    });

    it(`Given value is provided
        Then it renders a date picker formatted value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "2019-06-30",
            is_required: true,
            name: "date field",
            type: "date"
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata });
        const date_input = wrapper.find("[data-test=document-date-input]");

        expect(date_input.element.value).toEqual("2019-06-30");
        expect(date_input.element.required).toBe(true);
        expect(wrapper.contains("[data-test=document-custom-metadata-is-required]")).toBeTruthy();
    });
    it(`It does not render the component when type does not match`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "2019-06-30T00:00:00+03:00",
            is_required: true,
            name: "date field",
            type: "text"
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata });
        expect(wrapper.find("[data-test=document-custom-metadata-date]").exists()).toBeFalsy();
    });
});
