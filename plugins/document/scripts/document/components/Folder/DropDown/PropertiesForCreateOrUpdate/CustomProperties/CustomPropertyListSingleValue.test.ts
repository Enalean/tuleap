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
import CustomPropertyListSingleValue from "./CustomPropertyListSingleValue.vue";
import type { ListValue, Property } from "../../../../../type";
import emitter from "../../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import type { PropertiesState } from "../../../../../store/properties/module";
import { nextTick } from "vue";

jest.mock("../../../../../helpers/emitter");

describe("CustomPropertyListSingleValue.vue", () => {
    const store_options = { state: { properties: {} } };

    function createWrapper(
        property: Property,
    ): VueWrapper<InstanceType<typeof CustomPropertyListSingleValue>> {
        return shallowMount(CustomPropertyListSingleValue, {
            props: { currentlyUpdatedItemProperty: property },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        properties: {
                            namespaced: true,
                            state: {
                                project_properties: [
                                    {
                                        short_name: "list",
                                        allowed_list_values: [
                                            { id: 100, value: "None" },
                                            { id: 101, value: "abcde" },
                                            { id: 102, value: "fghij" },
                                        ],
                                    },
                                    {
                                        short_name: "an other list",
                                        allowed_list_values: [{ id: 100, name: "None" }],
                                    },
                                ],
                            } as unknown as PropertiesState,
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
            value: 101,
            is_required: false,
            type: "list",
            is_multiple_value_allowed: false,
        } as Property;
        const wrapper = createWrapper(currentlyUpdatedItemProperty);

        await nextTick();

        const all_options = wrapper
            .get("[data-test=document-custom-list-select]")
            .findAll("option");
        expect(all_options).toHaveLength(3);

        expect(wrapper.find("[data-test=document-custom-list-value-100]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-custom-list-value-101]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-custom-list-value-102]").exists()).toBeTruthy();
    });

    it(`Given a list property is required
        Then the input is required`, () => {
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            value: 101,
            is_required: true,
            type: "list",
            is_multiple_value_allowed: false,
        } as Property;
        const wrapper = createWrapper(currentlyUpdatedItemProperty);

        expect(wrapper.find("[data-test=document-custom-list-select]").exists()).toBeTruthy();

        const input = wrapper.get("[data-test=document-custom-list-select]");
        if (!(input.element instanceof HTMLSelectElement)) {
            throw new Error("Can not find list in DOM");
        }
        expect(input.element.required).toBe(true);
    });

    it(`does not render the component when type does not match`, () => {
        const currentlyUpdatedItemProperty = {
            short_name: "text",
            name: "custom text",
            value: "test",
            is_required: true,
            type: "text",
        } as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        expect(wrapper.find("[data-test=document-custom-property-list]").exists()).toBeFalsy();
    });

    it(`does not render the component when list is multiple`, () => {
        store_options.state.properties = {
            project_properties: [
                {
                    short_name: "list",
                    allowed_list_values: [{ id: 101, name: "abcde" }],
                },
            ],
        };

        const list_value: Array<ListValue> = [{ id: 101 } as ListValue];

        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: list_value,
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        } as Property;

        const wrapper = createWrapper(currentlyUpdatedItemProperty);
        expect(wrapper.find("[data-test=document-custom-property-list]").exists()).toBeFalsy();
    });

    it(`User can choose a list value`, async () => {
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            value: 101,
            is_required: true,
            type: "list",
            is_multiple_value_allowed: false,
        } as Property;
        const wrapper = createWrapper(currentlyUpdatedItemProperty);

        const input = wrapper.get("[data-test=document-custom-list-select]");
        if (!(input.element instanceof HTMLSelectElement)) {
            throw new Error("Can not find list in DOM");
        }

        await nextTick();

        wrapper.get("[data-test=document-custom-list-value-102]").setSelected();
        input.element.dispatchEvent(new Event("input"));

        await nextTick();

        expect(emitter.emit).toHaveBeenCalledWith("update-custom-property", {
            property_short_name: "list",
            value: "102",
        });
    });
});
