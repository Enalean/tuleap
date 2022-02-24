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
import FolderDefaultPropertiesForCreate from "./FolderDefaultPropertiesForCreate.vue";

describe("FolderDefaultPropertiesForCreate", () => {
    let default_property, store;
    beforeEach(() => {
        store = createStoreMock(
            { configuration: { is_status_property_used: true } },
            { properties: { has_loaded_properties: true } }
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
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: true,
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
        it(`Given item has custom property, default properties are rendered`, () => {
            store.state = {
                configuration: { is_status_property_used: true },
                properties: {
                    has_loaded_properties: true,
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
                    properties: [
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
        it(`Given item has no custom property and status is not available, default properties are not rendered`, () => {
            store.state = {
                configuration: { is_status_property_used: false },
                properties: {
                    has_loaded_properties: true,
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
                    properties: null,
                },
            });

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists()
            ).toBeFalsy();
        });
    });
});
