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
import { createStoreMock } from "../../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../../../helpers/local-vue";
import { TYPE_FILE } from "../../../../../constants";
import StatusPropertyWithCustomBindingForFolderCreate from "./StatusPropertyWithCustomBindingForFolderCreate.vue";

describe("StatusPropertyWithCustomBindingForFolderCreate", () => {
    let status_property, state, store;
    beforeEach(() => {
        state = {
            current_folder: {
                id: 4,
            },
            configuration: {
                is_status_property_used: false,
            },
        };

        const store_options = { state };

        store = createStoreMock(store_options);

        status_property = (props = {}) => {
            return shallowMount(StatusPropertyWithCustomBindingForFolderCreate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`display status selectbox only when status property is enabled for project`, async () => {
        const wrapper = status_property({
            currentlyUpdatedItem: {
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
            parent: {
                id: 40,
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
            },
        });

        store.state.configuration.is_status_property_used = true;
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=document-status-property-for-item-create]").exists()
        ).toBeTruthy();
    });

    it(`does not display status if property is not available`, () => {
        const wrapper = status_property({
            currentlyUpdatedItem: {
                type: TYPE_FILE,
                title: "title",
            },
            parent: {
                id: 40,
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
            },
        });

        store.state.configuration.is_status_property_used = false;

        expect(
            wrapper.find("[data-test=document-status-property-for-item-create]").exists()
        ).toBeFalsy();
        expect(wrapper.vm.status_value).toEqual(undefined);
    });

    it(`Status is inherit from current folder`, () => {
        const wrapper = status_property({
            currentlyUpdatedItem: {
                type: TYPE_FILE,
                title: "title",
            },
            parent: {
                id: 40,
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
            },
        });

        store.state.configuration.is_status_property_used = true;

        expect(wrapper.vm.status_value).toEqual("rejected");
    });

    it(`Given status value is updated Then the props used for document creation is updated`, () => {
        const wrapper = status_property({
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
            parent: {
                id: 40,
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
            },
        });

        store.state.configuration.is_status_property_used = true;

        wrapper.vm.status_value = "approved";

        expect(wrapper.vm.currentlyUpdatedItem.status).toEqual("approved");
    });
});
