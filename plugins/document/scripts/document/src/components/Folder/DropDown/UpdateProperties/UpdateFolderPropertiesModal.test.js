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

import { shallowMount } from "@vue/test-utils";

import UpdateFolderPropertiesModal from "./UpdateFolderPropertiesModal.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("UpdateFolderPropertiesModal", () => {
    let factory, store;

    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(UpdateFolderPropertiesModal, {
                mocks: { $store: store },
                props: { ...props },
                global: {
                    ...getGlobalTestOptions({
                        modules: {
                            configuration: {
                                state: {
                                    is_status_property_used: true,
                                    project_id: "102",
                                },
                                namespaced: true,
                            },
                            error: {
                                state: {
                                    has_modal_error: false,
                                },
                                namespaced: true,
                            },
                            properties: {
                                state: {
                                    has_loaded_properties: false,
                                },
                                namespaced: true,
                            },
                        },
                        state: {
                            current_folder: {
                                id: 42,
                                title: "My current folder",
                                properties: [
                                    {
                                        short_name: "title",
                                        name: "title",
                                        list_value: "My current folder",
                                        is_multiple_value_allowed: false,
                                        type: "text",
                                        is_required: false,
                                    },
                                    {
                                        short_name: "custom property",
                                        name: "custom",
                                        value: "value",
                                        is_multiple_value_allowed: false,
                                        type: "text",
                                        is_required: false,
                                    },
                                    {
                                        short_name: "status",
                                        list_value: [
                                            {
                                                id: 103,
                                            },
                                        ],
                                    },
                                ],
                            },
                        },
                    }),
                },
            });
        };

        jest.spyOn(tlp_modal, "createModal").mockReturnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {},
        });
    });
    describe("Events received by the modal -", () => {
        it(`Receives the property-recursion-list event,
       Then the properties_to_update  data is updated`, () => {
            const item = {
                id: 7,
                type: "folder",
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
            };

            const wrapper = factory({ item });
            emitter.emit("properties-recursion-list", {
                detail: { property_list: ["field_1"] },
            });

            expect(wrapper.vm.properties_to_update).toStrictEqual(["field_1"]);
        });
        it(`Receives the properties-recursion-option event,
       Then the properties_to_update  data is updated`, () => {
            const item = {
                id: 7,
                type: "folder",
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
            };

            const wrapper = factory({ item });
            emitter.emit("properties-recursion-option", {
                recursion_option: "all_items",
            });

            expect(wrapper.vm.recursion_option).toBe("all_items");
        });
        it(`Receives the update-status-recursion event because status is unchecked,
       Then the status recursion value is updated to none`, () => {
            const item = {
                id: 7,
                type: "folder",
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
            };

            const wrapper = factory({ item });
            emitter.emit("properties-recursion-option", {
                recursion_option: "all_items",
            });

            emitter.emit("update-status-recursion", false);

            expect(wrapper.vm.item_to_update.status.recursion).toBe("none");
        });
        it(`Receives the update-status-recursion event because status is checked,
       Then the status recursion value is updated to the recurse option value`, () => {
            const item = {
                id: 7,
                type: "folder",
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
            };

            const wrapper = factory({ item });
            emitter.emit("properties-recursion-option", {
                recursion_option: "all_items",
            });

            emitter.emit("update-status-recursion", true);

            expect(wrapper.vm.item_to_update.status.recursion).toBe("all_items");
        });
    });
    it("Transform item property rest representation", () => {
        const properties_to_update = {
            short_name: "field_1234",
            list_value: [
                {
                    id: 103,
                    value: "my custom displayed value",
                },
            ],
            type: "list",
            is_multiple_value_allowed: false,
        };

        const item = {
            id: 7,
            type: "folder",
            properties: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103,
                        },
                    ],
                },
                properties_to_update,
            ],
        };

        const expected_properties = {
            short_name: "field_1234",
            list_value: null,
            type: "list",
            is_multiple_value_allowed: false,
            recursion: "none",
            value: 103,
        };

        const wrapper = factory({ item });

        expect(wrapper.vm.formatted_item_properties).toEqual([expected_properties]);
    });

    it("Updates status", () => {
        const item = {
            id: 7,
            type: "folder",
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
        };

        const wrapper = factory({ item });

        expect(wrapper.vm.item_to_update.status).toStrictEqual({
            recursion: "none",
            value: "rejected",
        });

        emitter.emit("update-status-property", "draft");
        expect(wrapper.vm.item_to_update.status).toStrictEqual({
            recursion: "none",
            value: "draft",
        });
    });

    it("Updates title", () => {
        const item = {
            id: 7,
            type: "folder",
            title: "A folder",
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
        };

        const wrapper = factory({ item });

        expect(wrapper.vm.item_to_update.title).toBe("A folder");

        emitter.emit("update-title-property", "A folder updated");
        expect(wrapper.vm.item_to_update.title).toBe("A folder updated");
    });

    it("Updates description", () => {
        const item = {
            id: 7,
            type: "folder",
            description: "A custom description",
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
        };

        const wrapper = factory({ item });

        expect(wrapper.vm.item_to_update.description).toBe("A custom description");

        emitter.emit("update-description-property", "A description");
        expect(wrapper.vm.item_to_update.description).toBe("A description");
    });
});
