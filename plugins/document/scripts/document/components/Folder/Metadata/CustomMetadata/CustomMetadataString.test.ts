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

import localVue from "../../../../helpers/local-vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CustomMetadataString from "./CustomMetadataString.vue";
import type { Metadata } from "../../../../store/metadata/module";

describe("CustomMetadataString", () => {
    function createWrapper(metadata: Metadata): Wrapper<CustomMetadataString> {
        return shallowMount(CustomMetadataString, {
            localVue,
            propsData: { currentlyUpdatedItemMetadata: metadata },
        });
    }

    it(`renders an input with a required value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "string value",
            is_required: true,
            name: "field",
            type: "string",
        } as Metadata;
        const wrapper = createWrapper(currentlyUpdatedItemMetadata);
        const string_input = wrapper.get("[data-test=document-string-input]");

        if (!(string_input.element instanceof HTMLInputElement)) {
            throw new Error("Could not find string element in component");
        }
        expect(string_input.element.value).toEqual("string value");
        expect(string_input.element.required).toBe(true);
        expect(
            wrapper.find("[data-test=document-custom-metadata-is-required]").exists()
        ).toBeTruthy();
    });

    it(`renders an input with an empty value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            type: "string",
        } as Metadata;
        const wrapper = createWrapper(currentlyUpdatedItemMetadata);
        const string_input = wrapper.get("[data-test=document-string-input]");

        if (!(string_input.element instanceof HTMLInputElement)) {
            throw new Error("Could not find string element in component");
        }
        expect(string_input.element.value).toEqual("");
        expect(string_input.element.required).toBe(false);
        expect(
            wrapper.find("[data-test=document-custom-metadata-is-required]").exists()
        ).toBeFalsy();
    });

    it(`does not render the component when type does not match`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            type: "text",
        } as Metadata;

        const wrapper = createWrapper(currentlyUpdatedItemMetadata);
        expect(wrapper.find("[data-test=document-custom-metadata-string]").exists()).toBeFalsy();
    });
});
