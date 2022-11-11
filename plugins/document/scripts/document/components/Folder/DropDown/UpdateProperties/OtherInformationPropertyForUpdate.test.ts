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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import localVue from "../../../../helpers/local-vue";
import OtherInformationPropertiesForUpdate from "./OtherInformationPropertiesForUpdate.vue";
import { TYPE_FILE } from "../../../../constants";
import type { Item, ItemFile, ListValue, Property } from "../../../../type";

jest.mock("../../../../helpers/emitter");

describe("OtherInformationPropertiesForUpdate", () => {
    let store = {
        dispatch: jest.fn(),
    };

    function createWrapper(
        is_obsolescence_date_property_used: boolean,
        has_loaded_properties: boolean,
        item: Item,
        propertyToUpdate: Array<Property>
    ): Wrapper<OtherInformationPropertiesForUpdate> {
        const store_options = {
            state: {
                configuration: { is_obsolescence_date_property_used },
                properties: { has_loaded_properties },
            },
        };
        store = createStoreMock(store_options);
        return shallowMount(OtherInformationPropertiesForUpdate, {
            localVue,
            propsData: { currentlyUpdatedItem: item, value: "", propertyToUpdate },
            mocks: { $store: store },
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

            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-other-information-spinner]").exists()
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

            expect(store.dispatch).toHaveBeenCalledWith("properties/loadProjectProperties");
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

            await wrapper.vm.$nextTick();

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

            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
        });
    });
});
