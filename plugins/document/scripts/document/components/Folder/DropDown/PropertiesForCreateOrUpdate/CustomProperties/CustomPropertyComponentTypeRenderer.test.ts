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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CustomPropertyComponentTypeRenderer from "./CustomPropertyComponentTypeRenderer.vue";
import type { Property } from "../../../../../type";

describe("CustomPropertyComponentTypeRenderer", () => {
    function createWrapper(
        item_property: Property,
    ): VueWrapper<InstanceType<typeof CustomPropertyComponentTypeRenderer>> {
        return shallowMount(CustomPropertyComponentTypeRenderer, {
            props: { itemProperty: item_property },
        });
    }

    it(`Given custom string property
        Then it renders the corresponding component`, () => {
        const itemProperty = {
            short_name: "string",
            type: "string",
        } as Property;
        const wrapper = createWrapper(itemProperty);

        expect(wrapper.find("[data-test=document-custom-property-text]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-string]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-single]").exists(),
        ).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-date]").exists()).toBeFalsy();
    });
    it(`Given custom text property
        Then it renders the corresponding component`, () => {
        const itemProperty = {
            short_name: "text",
            type: "text",
        } as Property;
        const wrapper = createWrapper(itemProperty);

        expect(wrapper.find("[data-test=document-custom-property-text]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-custom-property-string]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-single]").exists(),
        ).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-date]").exists()).toBeFalsy();
    });
    it(`Given list with only one value property
        Then it renders the corresponding component`, () => {
        const itemProperty = {
            short_name: "list",
            type: "list",
            is_multiple_value_allowed: false,
        } as Property;
        const wrapper = createWrapper(itemProperty);

        expect(wrapper.find("[data-test=document-custom-property-text]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-string]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-single]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-date]").exists()).toBeFalsy();
    });

    it(`Given a list with multiple value property
        Then it renders the corresponding component`, () => {
        const itemProperty = {
            short_name: "list",
            type: "list",
            is_multiple_value_allowed: true,
        } as Property;
        const wrapper = createWrapper(itemProperty);

        expect(wrapper.find("[data-test=document-custom-property-text]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-string]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-single]").exists(),
        ).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists(),
        ).toBeTruthy();
        expect(wrapper.find("[data-test=document-custom-property-date]").exists()).toBeFalsy();
    });

    it(`Given a date value property
        Then it renders the corresponding component`, () => {
        const itemProperty = {
            short_name: "date",
            type: "date",
            is_multiple_value_allowed: false,
            value: "",
        } as Property;
        const wrapper = createWrapper(itemProperty);

        expect(wrapper.find("[data-test=document-custom-property-text]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-string]").exists()).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-single]").exists(),
        ).toBeFalsy();
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists(),
        ).toBeFalsy();
        expect(wrapper.find("[data-test=document-custom-property-date]").exists()).toBeTruthy();
    });
});
