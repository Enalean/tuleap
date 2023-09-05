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
import OtherInformationPropertiesForUpdate from "./OtherInformationPropertiesForUpdate.vue";
import { TYPE_FILE } from "../../../../constants";
import type { Item, ItemFile, ListValue, Property } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { ConfigurationState } from "../../../../store/configuration";
import type { PropertiesState } from "../../../../store/properties/module";
import { nextTick } from "vue";

jest.mock("../../../../helpers/emitter");

describe("OtherInformationPropertiesForUpdate", () => {
    let load_properties: jest.Mock;

    beforeEach(() => {
        load_properties = jest.fn();
    });

    function createWrapper(
        is_obsolescence_date_property_used: boolean,
        has_loaded_properties: boolean,
        item: Item,
        propertyToUpdate: Array<Property>,
    ): VueWrapper<InstanceType<typeof OtherInformationPropertiesForUpdate>> {
        load_properties.mockReset();
        return shallowMount(OtherInformationPropertiesForUpdate, {
            props: { currentlyUpdatedItem: item, value: "", propertyToUpdate },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                is_obsolescence_date_property_used,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                        properties: {
                            state: {
                                has_loaded_properties,
                            } as unknown as PropertiesState,
                            actions: {
                                loadProjectProperties: load_properties,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    describe("Custom properties", () => {
        it(`Given custom component are loading
        Then it displays spinner`, async () => {
            const properties: Array<Property> = [];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;
            const wrapper = createWrapper(true, false, item, []);

            await nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-other-information-spinner]").exists(),
            ).toBeTruthy();
        });

        it("Load project properties at first load", () => {
            const properties: Array<Property> = [];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;
            createWrapper(true, false, item, []);

            expect(load_properties).toHaveBeenCalled();
        });
    });

    describe("Other information display", () => {
        it(`Given obsolescence date is enabled for project
            Then we should display the obsolescence date component`, async () => {
            const properties: Array<Property> = [
                {
                    short_name: "obsolescence_date",
                    value: null,
                } as Property,
            ];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;

            const wrapper = createWrapper(true, true, item, []);

            await nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        });

        it(`Given project has custom properties
            Then we should display the other information section`, () => {
            const list_value: Array<ListValue> = [
                {
                    id: 103,
                } as ListValue,
            ];
            const properties: Array<Property> = [
                {
                    short_name: "field_1234",
                    list_value,
                    type: "list",
                    is_multiple_value_allowed: false,
                } as Property,
            ];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;

            const property = { short_name: "field_1234" } as Property;
            const wrapper = createWrapper(false, true, item, [property]);

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        });

        it(`Given obsolescence date is disabled for project and given no properties are provided
            Then other information section is not rendered`, async () => {
            const properties: Array<Property> = [];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;

            const wrapper = createWrapper(false, true, item, []);

            await nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
        });
    });
});
