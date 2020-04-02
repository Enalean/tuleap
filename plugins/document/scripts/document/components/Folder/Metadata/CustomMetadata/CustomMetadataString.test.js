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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import localVue from "../../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import CustomMetadataString from "./CustomMetadataString.vue";

describe("CustomMetadataString", () => {
    let factory;
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(CustomMetadataString, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it(`renders an input with a required value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "string value",
            is_required: true,
            name: "field",
            type: "string",
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });
        const date_input = wrapper.get("[data-test=document-string-input]");

        expect(date_input.element.value).toEqual("string value");
        expect(date_input.element.required).toBe(true);
        expect(wrapper.contains("[data-test=document-custom-metadata-is-required]")).toBeTruthy();
    });

    it(`renders an input with an empty value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            type: "string",
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });
        const date_input = wrapper.get("[data-test=document-string-input]");

        expect(date_input.element.value).toEqual("");
        expect(date_input.element.required).toBe(false);
        expect(wrapper.contains("[data-test=document-custom-metadata-is-required]")).toBeFalsy();
    });

    it(`does not render the component when type does not match`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            type: "text",
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata });
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeFalsy();
    });
});
