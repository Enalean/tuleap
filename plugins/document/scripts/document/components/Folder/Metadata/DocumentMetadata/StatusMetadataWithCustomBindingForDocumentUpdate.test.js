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
import { TYPE_FILE } from "../../../../constants.js";
import StatusMetadataWithCustomBindingForDocumentUpdate from "./StatusMetadataWithCustomBindingForDocumentUpdate.vue";

describe("StatusMetadataWithCustomBindingForDocumentUpdate", () => {
    let status_metadata, state, store;
    beforeEach(() => {
        state = {
            is_item_status_metadata_used: false,
        };

        const store_options = { state };

        store = createStoreMock(store_options);

        status_metadata = (props = {}) => {
            return shallowMount(StatusMetadataWithCustomBindingForDocumentUpdate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`display status selectbox only when status property is enabled for project`, async () => {
        const wrapper = status_metadata({
            currentlyUpdatedItem: {
                metadata: [
                    {
                        short_name: "status",
                        list_value: [
                            {
                                id: 100,
                            },
                        ],
                    },
                ],
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
        });

        store.state.is_item_status_metadata_used = true;
        await wrapper.vm.$nextTick();

        expect(wrapper.contains("[data-test=document-status-for-item-update]")).toBeTruthy();
    });

    it(`does not display status if property is not available`, () => {
        const wrapper = status_metadata({
            currentlyUpdatedItem: {
                metadata: [
                    {
                        short_name: "status",
                        list_value: [
                            {
                                id: 100,
                            },
                        ],
                    },
                ],
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
        });

        store.state.is_item_status_metadata_used = false;

        expect(wrapper.contains("[data-test=document-status-for-item-update]")).toBeFalsy();
    });

    it(`Given status value is updated Then the props used for document update is updated`, async () => {
        const wrapper = status_metadata({
            currentlyUpdatedItem: {
                metadata: [
                    {
                        short_name: "status",
                        list_value: [
                            {
                                id: 100,
                            },
                        ],
                    },
                ],
                type: TYPE_FILE,
                title: "title",
            },
        });

        store.state.is_item_status_metadata_used = true;
        await wrapper.vm.$nextTick();
        wrapper.vm.status_value = "approved";
        await wrapper.vm.$nextTick();

        expect(wrapper.contains("[data-test=document-status-for-item-update]")).toBeTruthy();

        expect(wrapper.vm.currentlyUpdatedItem.status).toEqual("approved");
    });
});
