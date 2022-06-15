/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import localVue from "../../../../helpers/local-vue";

import NewItemModal from "./NewItemModal.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import emitter from "../../../../helpers/emitter";
import * as tlp from "tlp";

jest.mock("tlp");

describe("NewItemModal", () => {
    let factory, store;

    beforeEach(() => {
        const general_store = {
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
                            description: "My current folder",
                            is_used: false,
                        },
                        {
                            short_name: "custom property",
                            name: "custom",
                            value: "value",
                            is_multiple_value_allowed: false,
                            type: "text",
                            is_required: false,
                            description: "Some Custom",
                            is_used: false,
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
                    permissions_for_groups: {
                        can_read: [],
                        can_write: [],
                        can_manage: [],
                    },
                },
                configuration: {
                    project_id: 102,
                    is_status_property_used: true,
                    is_obsolescence_date_property_used: true,
                },
            },
        };

        store = createStoreMock(general_store, {
            permissions: { project_ugroups: null },
            properties: {},
        });

        factory = (item = {}) => {
            return shallowMount(NewItemModal, {
                localVue,
                mocks: { $store: store },
                data() {
                    return {
                        item,
                        is_displayed: false,
                        is_loading: false,
                        modal: null,
                        parent: {},
                    };
                },
            });
        };

        jest.spyOn(tlp, "createModal").mockReturnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {},
        });
    });

    it("Updates custom property", () => {
        const item = {
            id: 7,
            title: "Color folder",
            type: "folder",
            description: "A custom description",
            properties: [
                {
                    short_name: "field_9",
                    name: "string 1",
                    value: "",
                    is_multiple_value_allowed: false,
                    type: "string",
                    is_required: true,
                    description: "",
                    is_used: false,
                },
            ],
        };

        const wrapper = factory(item);
        expect(wrapper.vm.item.properties[0].value).toBe("");
        emitter.emit("update-custom-property", {
            property_short_name: "field_9",
            value: "wololo some words",
        });
        expect(wrapper.vm.item.properties[0].value).toBe("wololo some words");
    });

    it("inherit default values from parent properties", async () => {
        const item_to_create = {
            properties: [
                {
                    short_name: "custom property",
                    name: "custom",
                    value: "value",
                    is_multiple_value_allowed: false,
                    type: "text",
                    is_required: false,
                    list_value: null,
                    allowed_list_values: null,
                    description: "Some Custom",
                    is_used: false,
                },
            ],
        };

        const wrapper = factory();
        emitter.emit("createItem", {
            item: store.state.current_folder,
        });
        await wrapper.vm.$nextTick().then(() => {});

        expect(wrapper.vm.item.properties).toStrictEqual(item_to_create.properties);
    });

    it("Updates status", () => {
        const item = {
            id: 7,
            title: "Color folder",
            type: "folder",
            status: "approved",
            properties: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 102,
                        },
                    ],
                },
            ],
        };

        const wrapper = factory(item);

        expect(wrapper.vm.item.status).toBe("approved");
        emitter.emit("update-status-property", "draft");
        expect(wrapper.vm.item.status).toBe("draft");
    });

    it("Updates title", () => {
        const item = {
            id: 7,
            title: "Color folder",
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

        const wrapper = factory(item);

        expect(wrapper.vm.item.title).toBe("Color folder");
        emitter.emit("update-title-property", "A folder");
        expect(wrapper.vm.item.title).toBe("A folder");
    });

    it("Updates description", () => {
        const item = {
            id: 7,
            title: "Color folder",
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

        const wrapper = factory(item);
        expect(wrapper.vm.item.description).toBe("A custom description");
        emitter.emit("update-description-property", "A description");
        expect(wrapper.vm.item.description).toBe("A description");
    });
});
