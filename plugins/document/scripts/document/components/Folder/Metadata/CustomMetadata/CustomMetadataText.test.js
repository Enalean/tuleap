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
import CustomMetadataText from "./CustomMetadataText.vue";

describe("CustomMetadataText", () => {
    let factory;
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(CustomMetadataText, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it(`renders an input with a required value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "text value",
            is_required: true,
            name: "field",
            type: "text",
            short_name: "short_name",
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });
        const date_input = wrapper.get("[data-test=document-text-input]");

        expect(date_input.element.value).toEqual("text value");
        expect(date_input.element.required).toBe(true);
        expect(wrapper.contains("[data-test=document-custom-metadata-is-required]")).toBeTruthy();
    });

    it(`renders an input with an empty value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            type: "text",
            short_name: "short_name",
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });
        const date_input = wrapper.get("[data-test=document-text-input]");

        expect(date_input.element.value).toEqual("");
        expect(date_input.element.required).toBe(false);
        expect(wrapper.contains("[data-test=document-custom-metadata-is-required]")).toBeFalsy();
    });

    it(`Given custom text metadata
        Then it renders the corresponding component`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            short_name: "text",
            type: "text",
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeTruthy();
    });
});
