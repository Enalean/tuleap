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
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import localVue from "../../../helpers/local-vue.js";
import GlobalMetadata from "./GlobalMetadata.vue";
import { TYPE_FILE } from "../../../constants.js";

describe("GlobalMetadata", () => {
    let global_metadata, state, store;
    beforeEach(() => {
        state = {
            is_item_status_metadata_used: false
        };

        const store_options = { state };

        store = createStoreMock(store_options);

        global_metadata = (props = {}) => {
            return shallowMount(GlobalMetadata, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });
    it(`Given status is enabled for project
        Then we should display the status component`, () => {
        const wrapper = global_metadata(
            {
                currentlyUpdatedItem: {
                    metadata: [
                        {
                            short_name: "status",
                            list_value: [
                                {
                                    id: 100
                                }
                            ]
                        }
                    ],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title"
                }
            },
            { parent: 102 }
        );

        store.state.is_item_status_metadata_used = true;

        expect(wrapper.find("[data-test=document-status-metadata]").exists()).toBeTruthy();
    });

    it(`Given status is disabled for project
        Then status component is not rendered`, () => {
        const wrapper = global_metadata(
            {
                currentlyUpdatedItem: {
                    metadata: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title"
                }
            },
            { parent: 102 }
        );

        store.state.is_item_status_metadata_used = false;

        expect(wrapper.find("[data-test=document-status-metadata]").exists()).toBeFalsy();
    });

    describe("Given status value is updated", () => {
        it(`Then the props used for document creation is updated`, () => {
            const wrapper = global_metadata(
                {
                    currentlyUpdatedItem: {
                        metadata: [
                            {
                                short_name: "status",
                                list_value: [
                                    {
                                        id: 100
                                    }
                                ]
                            }
                        ],
                        status: 100,
                        type: TYPE_FILE,
                        title: "title"
                    }
                },
                { parent: 102 }
            );

            store.state.is_item_status_metadata_used = true;

            wrapper.vm.status_value = 102;

            expect(wrapper.vm.currentlyUpdatedItem.status).toEqual("approved");
        });

        it(`Then the props used for document update is updated`, () => {
            const wrapper = global_metadata(
                {
                    currentlyUpdatedItem: {
                        metadata: [
                            {
                                short_name: "status",
                                list_value: [
                                    {
                                        id: 100
                                    }
                                ]
                            }
                        ],
                        status: 100,
                        type: TYPE_FILE,
                        title: "title"
                    }
                },
                { parent: 102 }
            );

            store.state.is_item_status_metadata_used = true;

            wrapper.vm.status_value = 102;
            expect(wrapper.vm.currentlyUpdatedItem.metadata[0].list_value[0].id).toEqual(102);
            expect(wrapper.vm.currentlyUpdatedItem.metadata[0].list_value[0].name).toEqual(
                "approved"
            );
        });
    });
});
