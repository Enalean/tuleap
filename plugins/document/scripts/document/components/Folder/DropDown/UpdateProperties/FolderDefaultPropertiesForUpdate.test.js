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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../../helpers/local-vue";
import FolderDefaultPropertiesForUpdate from "./FolderDefaultPropertiesForUpdate.vue";
import { TYPE_FILE } from "../../../../constants";
import emitter from "../../../../helpers/emitter";

jest.mock("../../../../helpers/emitter");

describe("FolderDefaultPropertiesForUpdate", () => {
    let default_property, store;
    beforeEach(() => {
        store = createStoreMock(
            {},
            {
                properties: { has_loaded_properties: true },
                configuration: { is_status_property_used: true },
            }
        );

        default_property = (props = {}) => {
            return shallowMount(FolderDefaultPropertiesForUpdate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    describe("Component loading -", () => {
        it("Load project properties at first load", () => {
            store.state = {
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: false,
                },
            };

            default_property({
                currentlyUpdatedItem: {
                    properties: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                itemProperty: [],
            });

            expect(store.dispatch).toHaveBeenCalledWith("properties/loadProjectProperties");
        });

        it(`Given custom component are loading
            Then it displays spinner`, async () => {
            const wrapper = default_property({
                currentlyUpdatedItem: {
                    properties: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                itemProperty: [],
            });

            store.state = {
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: false,
                },
            };
            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-folder-default-properties-spinner]").exists()
            ).toBeTruthy();
        });
    });

    describe("Component display -", () => {
        it(`Given project uses status, default properties are rendered`, () => {
            store.state = {
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: [
                        {
                            short_name: "status",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                    ],
                    status: {
                        value: "rejected",
                        recursion: "none",
                    },
                },
                itemProperty: [],
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
        });
        it(`Given item has custom properties, default properties are rendered`, () => {
            store.state = {
                configuration: { is_status_property_used: false },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: [
                        {
                            short_name: "field_",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                    ],
                },
                itemProperty: [{ id: 100 }],
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
        });
        it(`Given item has no custom properties and status is not available, default properties are not rendered`, () => {
            store.state = {
                configuration: { is_status_property_used: false },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: null,
                },
                itemProperty: [],
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeFalsy();
        });
    });

    describe("Apply bindings -", () => {
        it(`Given recursion option is updated Then the props used for document creation is updated`, () => {
            store.state = {
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: [
                        {
                            short_name: "field_",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                    ],
                    status: {
                        value: "rejected",
                        recursion: "none",
                    },
                },
                itemProperty: [],
            });

            wrapper.vm.recursion_option = "all_items";

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
            expect(wrapper.vm.currentlyUpdatedItem.status.recursion).toBe("all_items");
            expect(wrapper.vm.recursion).toBe("all_items");
        });

        it(`Emit event on check recursion for item`, () => {
            store.state = {
                configuration: { is_status_property_used: false },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: [
                        {
                            short_name: "field_",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                    ],
                },
                itemProperty: [
                    {
                        short_name: "field_",
                        list_value: [103],
                    },
                ],
            });

            store.state.configuration.is_status_property_used = true;

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();

            const input = wrapper.get("[data-test=document-custom-property-checkbox]");
            input.trigger("change");

            expect(emitter.emit).toHaveBeenCalledWith("properties-recursion-list", {
                detail: { property_list: [] },
            });
        });
    });
    describe("The checkbox value according to the recursion option -", () => {
        it(`Given "all_items" recursion option
        then all properties should be checked`, async () => {
            store.state = {
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: [
                        {
                            short_name: "field_1",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                        {
                            short_name: "field_2",
                            value: "non",
                        },
                        {
                            short_name: "field_3",
                            list_value: [
                                {
                                    id: 100,
                                },
                            ],
                        },
                    ],
                    status: {
                        value: "rejected",
                        recursion: "none",
                    },
                },
                itemProperty: [
                    {
                        short_name: "field_1",
                        list_value: [103],
                    },
                    {
                        short_name: "field_2",
                        value: "non",
                    },
                    {
                        short_name: "field_3",
                        list_value: [100],
                    },
                ],
            });

            wrapper.vm.recursion_option = "all_items";

            const input = wrapper.get("[data-test=document-custom-property-recursion-option]");
            input.element.value = "all_items";

            expect(wrapper.vm.properties_to_update).toEqual([]);

            await wrapper.vm.updateRecursionOption();

            expect(wrapper.vm.properties_to_update).toEqual([
                "field_1",
                "field_2",
                "field_3",
                "status",
            ]);
        });

        it(`Given "all_items" recursion option
        then the status properties is not in the update list if the status is not enabled for the project`, async () => {
            store.state = {
                configuration: { is_status_property_used: false },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: [
                        {
                            short_name: "field_1",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                        {
                            short_name: "field_2",
                            value: "non",
                        },
                        {
                            short_name: "field_3",
                            list_value: [
                                {
                                    id: 100,
                                },
                            ],
                        },
                    ],
                    status: {
                        value: "rejected",
                        recursion: "none",
                    },
                },
                itemProperty: [
                    {
                        short_name: "field_1",
                        list_value: [103],
                    },
                    {
                        short_name: "field_2",
                        value: "non",
                    },
                    {
                        short_name: "field_3",
                        list_value: [100],
                    },
                ],
            });

            wrapper.vm.recursion_option = "all_items";

            const input = wrapper.get("[data-test=document-custom-property-recursion-option]");
            input.element.value = "all_items";

            expect(wrapper.vm.properties_to_update).toEqual([]);

            await wrapper.vm.updateRecursionOption();

            expect(wrapper.vm.properties_to_update).toEqual(["field_1", "field_2", "field_3"]);
        });

        it(`Given "none" recursion option
        then the status properties is not in the update list is empty, all the checkbox are unchecked`, async () => {
            store.state = {
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: true,
                },
            };

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    properties: [
                        {
                            short_name: "field_1",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                        },
                        {
                            short_name: "field_2",
                            value: "non",
                        },
                        {
                            short_name: "field_3",
                            list_value: [
                                {
                                    id: 100,
                                },
                            ],
                        },
                    ],
                    status: {
                        value: "rejected",
                        recursion: "none",
                    },
                },
                itemProperty: [],
            });

            wrapper.vm.recursion_option = "none";

            wrapper.vm.properties_to_update = ["field_1", "field_3"];

            const input = wrapper.get("[data-test=document-custom-property-recursion-option]");
            input.element.value = "none";

            expect(wrapper.vm.properties_to_update).toEqual(["field_1", "field_3"]);

            await wrapper.vm.updateRecursionOption();

            expect(wrapper.vm.properties_to_update).toEqual([]);
        });
    });
});
