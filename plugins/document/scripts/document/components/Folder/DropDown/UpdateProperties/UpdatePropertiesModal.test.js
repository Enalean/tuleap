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
import emitter from "../../../../helpers/emitter";

import UpdatePropertiesModal from "./UpdatePropertiesModal.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("UpdatePropertiesModal", () => {
    let factory;

    beforeEach(() => {
        factory = (item, has_loaded_properties) => {
            return shallowMount(UpdatePropertiesModal, {
                props: { item },
                global: {
                    ...getGlobalTestOptions({
                        modules: {
                            configuration: {
                                state: {
                                    project_id: 102,
                                    is_status_property_used: true,
                                    has_loaded_properties,
                                },
                                namespaced: true,
                            },
                            error: {
                                state: {
                                    has_global_modal_error: false,
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

    it("Updates owner", () => {
        const item = {
            id: 7,
            type: "folder",
            description: "A custom description",
            owner: {
                id: 101,
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
        };

        const wrapper = factory(item, true);

        expect(wrapper.vm.item_to_update.owner.id).toBe(101);

        emitter.emit("update-owner-property", 200);
        expect(wrapper.vm.item_to_update.owner.id).toBe(200);
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

        const properties_in_rest_format = {
            short_name: "field_1234",
            list_value: null,
            recursion: "none",
            type: "list",
            is_multiple_value_allowed: false,
            value: 103,
        };

        const wrapper = factory(item, false);

        expect(wrapper.vm.formatted_item_properties).toEqual([properties_in_rest_format]);
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

        const wrapper = factory(item, true);

        expect(wrapper.vm.item_to_update.status).toBe("rejected");

        emitter.emit("update-status-property", "draft");
        expect(wrapper.vm.item_to_update.status).toBe("draft");
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

        const wrapper = factory(item, true);

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

        const wrapper = factory(item, true);

        expect(wrapper.vm.item_to_update.description).toBe("A custom description");

        emitter.emit("update-description-property", "A description");
        expect(wrapper.vm.item_to_update.description).toBe("A description");
    });
});
