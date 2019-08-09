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
import localVue from "../../../helpers/local-vue.js";
import { tlp } from "tlp-mocks";

import NewItemModal from "./NewItemModal.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

import EventBus from "../../../helpers/event-bus.js";

describe("NewItemModal", () => {
    let factory, store;

    beforeEach(() => {
        const general_store = {
            state: {
                current_folder: {
                    id: 42,
                    title: "My current folder",
                    metadata: [
                        {
                            short_name: "title",
                            name: "title",
                            list_value: "My current folder",
                            is_multiple_value_allowed: false,
                            type: "text"
                        },
                        {
                            short_name: "custom metadata",
                            name: "custom",
                            value: "value",
                            is_multiple_value_allowed: false,
                            type: "text"
                        }
                    ]
                },
                is_obsolescence_date_metadata_used: true,
                is_item_status_metadata_used: true,
                project_id: 102
            }
        };

        store = createStoreMock(general_store, { metadata: {} });

        factory = () => {
            return shallowMount(NewItemModal, {
                localVue,
                mocks: { $store: store }
            });
        };

        tlp.modal.and.returnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {}
        });
    });

    afterEach(() => {});

    it("Does not load project metadata, when they have already been loaded", async () => {
        store.state.metadata = {
            has_loaded_metadata: true
        };

        const wrapper = factory();

        EventBus.$emit("show-new-document-modal", {
            detail: { parent: store.state.current_folder }
        });
        await wrapper.vm.$nextTick().then(() => {});

        expect(store.dispatch).not.toHaveBeenCalledWith("metadata/loadProjectMetadata");
    });

    it("It inherit default values from parent metadata", async () => {
        const item_to_create = {
            metadata: [
                {
                    short_name: "custom metadata",
                    name: "custom",
                    value: "value",
                    is_multiple_value_allowed: false,
                    type: "text"
                }
            ]
        };

        const wrapper = factory();

        EventBus.$emit("show-new-document-modal", {
            detail: { parent: store.state.current_folder }
        });
        await wrapper.vm.$nextTick().then(() => {});

        expect(wrapper.vm.item.metadata).toEqual(item_to_create.metadata);
    });
});
