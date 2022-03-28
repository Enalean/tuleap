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
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
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

        factory = () => {
            return shallowMount(NewItemModal, {
                localVue,
                mocks: { $store: store },
            });
        };

        jest.spyOn(tlp, "createModal").mockReturnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {},
        });
    });

    it("Does not load project properties, when they have already been loaded", async () => {
        store.state.properties = {
            has_loaded_properties: true,
        };

        const wrapper = factory();

        await wrapper.vm.$nextTick().then(() => {});

        expect(store.dispatch).not.toHaveBeenCalledWith("properties/loadProjectProperties");
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
                },
            ],
        };

        const wrapper = factory();
        emitter.emit("createItem", {
            item: store.state.current_folder,
        });
        await wrapper.vm.$nextTick().then(() => {});

        expect(wrapper.vm.item.properties).toEqual(item_to_create.properties);
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

        emitter.emit("update-status-property", "draft");
        expect(wrapper.vm.item.status).toEqual("draft");
    });
});
