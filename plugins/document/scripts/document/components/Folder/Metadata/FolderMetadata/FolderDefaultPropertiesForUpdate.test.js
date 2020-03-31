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
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../../helpers/local-vue.js";
import FolderDefaultPropertiesForUpdate from "./FolderDefaultPropertiesForUpdate.vue";
import { TYPE_FILE } from "../../../../constants.js";
import EventBus from "../../../../helpers/event-bus.js";

describe("FolderDefaultPropertiesForUpdate", () => {
    let default_property, store;
    beforeEach(() => {
        store = createStoreMock(
            { is_item_status_metadata_used: true },
            { metadata: { has_loaded_metadata: true } }
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
        it("Load project metadata at first load", () => {
            store.state = {
                is_item_status_metadata_used: true,
                metadata: {
                    has_loaded_metadata: false,
                },
            };

            default_property({
                currentlyUpdatedItem: {
                    metadata: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                itemMetadata: [],
            });

            expect(store.dispatch).toHaveBeenCalledWith("metadata/loadProjectMetadata", [store]);
        });

        it(`Given custom component are loading
            Then it displays spinner`, async () => {
            const wrapper = default_property({
                currentlyUpdatedItem: {
                    metadata: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                itemMetadata: [],
            });

            store.state = {
                is_item_status_metadata_used: true,
                metadata: {
                    has_loaded_metadata: false,
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
                is_item_status_metadata_used: true,
                metadata: {
                    has_loaded_metadata: true,
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
                    metadata: [
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
                itemMetadata: [],
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
        });
        it(`Given item has custom metadata, default properties are rendered`, () => {
            store.state = {
                is_item_status_metadata_used: false,
                metadata: {
                    has_loaded_metadata: true,
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
                    metadata: [
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
                itemMetadata: [{ id: 100 }],
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
        });
        it(`Given item has no custom metadata and status is not available, default properties are not rendered`, () => {
            store.state = {
                is_item_status_metadata_used: false,
                metadata: {
                    has_loaded_metadata: true,
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
                    metadata: null,
                },
                itemMetadata: [],
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeFalsy();
        });
    });

    describe("Apply bindings -", () => {
        it(`Given recursion option is updated Then the props used for document creation is updated`, () => {
            store.state = {
                is_item_status_metadata_used: true,
                metadata: {
                    has_loaded_metadata: true,
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
                    metadata: [
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
                itemMetadata: [],
            });

            wrapper.vm.recursion_option = "all_items";

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
            expect(wrapper.vm.currentlyUpdatedItem.status.recursion).toEqual("all_items");
            expect(wrapper.vm.recursion).toEqual("all_items");
        });

        it(`Emit event on check recursion for item`, () => {
            store.state = {
                is_item_status_metadata_used: false,
                metadata: {
                    has_loaded_metadata: true,
                },
            };

            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const wrapper = default_property({
                currentlyUpdatedItem: {
                    id: 123,
                    title: "My title",
                    description: "My description",
                    owner: {
                        id: 102,
                    },
                    metadata: [
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
                itemMetadata: [
                    {
                        short_name: "field_",
                        list_value: [103],
                    },
                ],
            });

            store.state.is_item_status_metadata_used = true;

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();

            const input = wrapper.get("[data-test=document-custom-metadata-checkbox]");
            input.trigger("change");

            expect(event_bus_emit).toHaveBeenCalledWith("metadata-recursion-metadata-list", {
                detail: { metadata_list: [] },
            });
        });
    });
    describe("The checkbox value according to the recursion option -", () => {
        it(`Given "all_items" recursion option
        then all metadata should be checked`, async () => {
            store.state = {
                is_item_status_metadata_used: true,
                metadata: {
                    has_loaded_metadata: true,
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
                    metadata: [
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
                itemMetadata: [
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

            const input = wrapper.get("[data-test=document-custom-metadata-recursion-option]");
            input.element.value = "all_items";

            expect(wrapper.vm.metadata_list_to_update).toEqual([]);

            await wrapper.vm.updateRecursionOption();

            expect(wrapper.vm.metadata_list_to_update).toEqual([
                "field_1",
                "field_2",
                "field_3",
                "status",
            ]);
        });

        it(`Given "all_items" recursion option
        then the status metadata is not in the update list if the status is not enabled for the project`, async () => {
            store.state = {
                is_item_status_metadata_used: false,
                metadata: {
                    has_loaded_metadata: true,
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
                    metadata: [
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
                itemMetadata: [
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

            const input = wrapper.get("[data-test=document-custom-metadata-recursion-option]");
            input.element.value = "all_items";

            expect(wrapper.vm.metadata_list_to_update).toEqual([]);

            await wrapper.vm.updateRecursionOption();

            expect(wrapper.vm.metadata_list_to_update).toEqual(["field_1", "field_2", "field_3"]);
        });

        it(`Given "none" recursion option
        then the status metadata is not in the update list is empty, all the checkbox are unchecked`, async () => {
            store.state = {
                is_item_status_metadata_used: true,
                metadata: {
                    has_loaded_metadata: true,
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
                    metadata: [
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
                itemMetadata: [],
            });

            wrapper.vm.recursion_option = "none";

            wrapper.vm.metadata_list_to_update = ["field_1", "field_3"];

            const input = wrapper.get("[data-test=document-custom-metadata-recursion-option]");
            input.element.value = "none";

            expect(wrapper.vm.metadata_list_to_update).toEqual(["field_1", "field_3"]);

            await wrapper.vm.updateRecursionOption();

            expect(wrapper.vm.metadata_list_to_update).toEqual([]);
        });
    });
});
