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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CustomMetadataList from "./CustomMetadataListSingleValue.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { ListValue, Metadata } from "../../../../store/metadata/module";
import localVue from "../../../../helpers/local-vue";

describe("CustomMetadataListSingleValue", () => {
    const store_options = { state: { metadata: {} } };
    function createWrapper(metadata: Metadata): Wrapper<CustomMetadataList> {
        const store = createStoreMock(store_options);
        return shallowMount(CustomMetadataList, {
            localVue,
            propsData: { currentlyUpdatedItemMetadata: metadata },
            mocks: {
                $store: store,
            },
        });
    }

    it(`Given a list metadata
        Then it renders only the possible values of this list metadata`, async () => {
        store_options.state.metadata = {
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
        } as Metadata;
        const wrapper = createWrapper(currentlyUpdatedItemMetadata);

        await wrapper.vm.$nextTick();

        const all_options = wrapper
            .get("[data-test=document-custom-list-select]")
            .findAll("option");
        expect(all_options.length).toBe(3);

        expect(wrapper.find("[data-test=document-custom-list-value-100]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-custom-list-value-101]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-custom-list-value-102]").exists()).toBeTruthy();
    });

    it(`Given a list metadata is required
        Then the input is required`, () => {
        store_options.state.metadata = {
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
        } as Metadata;
        const wrapper = createWrapper(currentlyUpdatedItemMetadata);

        expect(wrapper.find("[data-test=document-custom-list-select]").exists()).toBeTruthy();

        const input = wrapper.get("[data-test=document-custom-list-select]");
        if (!(input.element instanceof HTMLSelectElement)) {
            throw new Error("Can not find list in DOM");
        }
        expect(input.element.required).toBe(true);
    });

    it(`does not render the component when type does not match`, () => {
        store_options.state.metadata = {
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
        } as Metadata;

        const wrapper = createWrapper(currentlyUpdatedItemMetadata);
        expect(wrapper.find("[data-test=document-custom-metadata-list]").exists()).toBeFalsy();
    });

    it(`does not render the component when list is multiple`, () => {
        store_options.state.metadata = {
            project_metadata_list: [
                {
                    short_name: "list",
                    allowed_list_values: [{ id: 101, value: "abcde" }],
                },
            ],
        };

        const list_value: Array<ListValue> = [{ id: 101 } as ListValue];

        const currentlyUpdatedItemMetadata = {
            short_name: "list",
            name: "custom list",
            list_value: list_value,
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        } as Metadata;

        const wrapper = createWrapper(currentlyUpdatedItemMetadata);
        expect(wrapper.find("[data-test=document-custom-metadata-list]").exists()).toBeFalsy();
    });
});
