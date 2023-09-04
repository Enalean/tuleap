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
import CustomPropertyListMultipleValue from "./CustomPropertyListMultipleValue.vue";
import emitter from "../../../../../helpers/emitter";
import type { ListValue, Property } from "../../../../../type";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import type { PropertiesState } from "../../../../../store/properties/module";
import { nextTick } from "vue";

jest.mock("../../../../../helpers/emitter");

describe("CustomPropertyListMultipleValue", () => {
    const allowed_list_values: Array<ListValue> = [
        { id: 100, name: "None" },
        { id: 101, name: "abcde" },
        { id: 102, name: "fghij" },
    ];

    function createWrapper(
        property: Property,
    ): VueWrapper<InstanceType<typeof CustomPropertyListMultipleValue>> {
        return shallowMount(CustomPropertyListMultipleValue, {
            props: { currentlyUpdatedItemProperty: property },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        properties: {
                            state: {
                                project_properties: [
                                    {
                                        short_name: "list",
                                        allowed_list_values,
                                    } as Property,
                                    {
                                        short_name: "an other list",
                                        allowed_list_values: [{ id: 100, name: "None" }],
                                    } as Property,
                                ],
                            } as unknown as PropertiesState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it(`Given a list property
        Then it renders only the possible values of this list property`, async () => {
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: false,
            type: "list",
            is_multiple_value_allowed: true,
        } as unknown as Property;
        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        await nextTick();

        const all_options = wrapper
            .get("[data-test=document-custom-list-multiple-select]")
            .findAll("option");

        expect(all_options).toHaveLength(3);
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-value-100]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-value-101]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-value-102]").exists(),
        ).toBeTruthy();
    });
    it(`Given a list property is required
        Then the input is required`, () => {
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        } as unknown as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-select]").exists(),
        ).toBeTruthy();

        expect(
            wrapper.find("[data-test=document-custom-property-is-required]").exists(),
        ).toBeTruthy();
    });

    it(`DOES NOT renders the component when there is only one value allowed for the list`, () => {
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            value: 101,
            is_required: true,
            type: "list",
            is_multiple_value_allowed: false,
        } as unknown as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists(),
        ).toBeFalsy();
    });

    it(`does not render the component when type does not match`, async () => {
        const currentlyUpdatedItemProperty = {
            short_name: "text",
            name: "custom text",
            value: 101,
            is_required: true,
            type: "text",
            is_multiple_value_allowed: true,
        } as unknown as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        await nextTick();
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists(),
        ).toBeFalsy();
    });

    it(`throws an event when list value is changed`, async () => {
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        } as unknown as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        await nextTick();
        wrapper.get("[data-test=document-custom-list-multiple-value-102]").setSelected();
        wrapper.get("[data-test=document-custom-list-multiple-select]").trigger("change");

        expect(emitter.emit).toHaveBeenCalledWith("update-multiple-properties-list-value", {
            detail: {
                value: expect.anything(),
                id: "list",
            },
        });
    });
});
