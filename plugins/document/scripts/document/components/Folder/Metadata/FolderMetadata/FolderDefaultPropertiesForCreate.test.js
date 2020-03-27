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
import FolderDefaultPropertiesForCreate from "./FolderDefaultPropertiesForCreate.vue";

describe("FolderDefaultPropertiesForCreate", () => {
    let default_property, store;
    beforeEach(() => {
        store = createStoreMock(
            { is_item_status_metadata_used: true },
            { metadata: { has_loaded_metadata: true } }
        );

        default_property = (props = {}) => {
            return shallowMount(FolderDefaultPropertiesForCreate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
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
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
        });
        it(`Given item has custom metadata, default properties are rendered`, () => {
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
                },
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-folder-default-properties]").exists()
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
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeFalsy();
        });
    });
});
