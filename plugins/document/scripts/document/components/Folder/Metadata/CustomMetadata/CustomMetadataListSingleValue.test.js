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

import localVue from "../../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import CustomMetadataList from "./CustomMetadataListSingleValue.vue";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";

describe("CustomMetadataList", () => {
    let store, factory;
    beforeEach(() => {
        store = createStoreMock({}, { metadata: {} });

        factory = (props = {}) => {
            return shallowMount(CustomMetadataList, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given a list metadata
        Then it renders only the possible values of this list metadata`, async () => {
        store.state.metadata = {
            project_metadata_list: [
                {
                    short_name: "list",
                    allowed_list_values: [
                        { id: 100, value: "None" },
                        { id: 101, value: "abcde" },
                        { id: 102, value: "fghij" },
                    ],
                },
                {
                    short_name: "an other list",
                    allowed_list_values: [{ id: 100, value: "None" }],
                },
            ],
        };

        const currentlyUpdatedItemMetadata = {
            short_name: "list",
            name: "custom list",
            value: 101,
            is_required: false,
            type: "list",
            is_multiple_value_allowed: false,
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });

        await wrapper.vm.$nextTick().then(() => {});

        const all_options = wrapper
            .get("[data-test=document-custom-list-select]")
            .findAll("option");
        expect(all_options.length).toBe(3);

        expect(wrapper.contains("[data-test=document-custom-list-value-100]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-custom-list-value-101]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-custom-list-value-102]")).toBeTruthy();
    });

    it(`Given a list metadata is required
        Then the input is required`, () => {
        store.state.metadata = {
            project_metadata_list: [
                {
                    short_name: "list",
                    allowed_list_values: [{ id: 101, value: "abcde" }],
                },
            ],
        };

        const currentlyUpdatedItemMetadata = {
            short_name: "list",
            name: "custom list",
            value: 101,
            is_required: true,
            type: "list",
            is_multiple_value_allowed: false,
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });

        expect(wrapper.contains("[data-test=document-custom-list-select]")).toBeTruthy();

        const input = wrapper.get("[data-test=document-custom-list-select]");
        expect(input.element.required).toBe(true);
    });

    it(`does not render the component when type does not match`, () => {
        store.state.metadata = {
            project_metadata_list: [
                {
                    short_name: "list",
                    allowed_list_values: [{ id: 101, value: "abcde" }],
                },
            ],
        };

        const currentlyUpdatedItemMetadata = {
            short_name: "text",
            name: "custom text",
            value: "test",
            is_required: true,
            type: "text",
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata });
        expect(wrapper.contains("[data-test=document-custom-metadata-list]")).toBeFalsy();
    });

    it(`does not render the component when list is multiple`, () => {
        store.state.metadata = {
            project_metadata_list: [
                {
                    short_name: "list",
                    allowed_list_values: [{ id: 101, value: "abcde" }],
                },
            ],
        };

        const currentlyUpdatedItemMetadata = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata });
        expect(wrapper.contains("[data-test=document-custom-metadata-list]")).toBeFalsy();
    });
});
