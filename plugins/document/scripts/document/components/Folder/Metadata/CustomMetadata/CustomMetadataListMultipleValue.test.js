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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import localVue from "../../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import CustomMetadataListMultipleValue from "./CustomMetadataListMultipleValue.vue";
import EventBus from "../../../../helpers/event-bus.js";

describe("CustomMetadataListMultipleValue", () => {
    let store, factory;
    beforeEach(() => {
        store = createStoreMock({}, { metadata: {} });

        factory = (props = {}) => {
            return shallowMount(CustomMetadataListMultipleValue, {
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
            list_value: [101],
            is_required: false,
            type: "list",
            is_multiple_value_allowed: true,
        };
        const wrapper = factory({ currentlyUpdatedItemMetadata });

        await wrapper.vm.$nextTick().then(() => {});

        const all_options = wrapper
            .get("[data-test=document-custom-list-multiple-select]")
            .findAll("option");

        expect(all_options.length).toBe(3);
        expect(
            wrapper.contains("[data-test=document-custom-list-multiple-value-100]")
        ).toBeTruthy();
        expect(
            wrapper.contains("[data-test=document-custom-list-multiple-value-101]")
        ).toBeTruthy();
        expect(
            wrapper.contains("[data-test=document-custom-list-multiple-value-102]")
        ).toBeTruthy();
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
            list_value: [101],
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata });
        expect(wrapper.contains("[data-test=document-custom-list-multiple-select]")).toBeTruthy();

        expect(wrapper.contains("[data-test=document-custom-metadata-is-required]")).toBeTruthy();
    });

    it(`Given a list metadata is updated
        Then the binding is updated as well`, () => {
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

        wrapper.vm.multiple_list_values.values = [100, 102];
        expect(wrapper.vm.currentlyUpdatedItemMetadata.list_value.values).toEqual([100, 102]);
        expect(wrapper.vm.$data.project_metadata_list_possible_values).toEqual(
            store.state.metadata.project_metadata_list[0]
        );
    });

    it(`DOES NOT renders the component when there is only one value allowed for the list`, () => {
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
        expect(wrapper.contains("[data-test=document-custom-metadata-list-multiple]")).toBeFalsy();
        expect(wrapper.vm.$data.project_metadata_list_possible_values).toEqual([]);
    });

    it(`does not render the component when type does not match`, () => {
        store.state.metadata = {
            project_metadata_list: [
                {
                    short_name: "text",
                    allowed_list_values: [
                        { id: 100, value: "None" },
                        { id: 101, value: "abcde" },
                        { id: 102, value: "fghij" },
                    ],
                },
            ],
        };

        const currentlyUpdatedItemMetadata = {
            short_name: "text",
            name: "custom text",
            value: 101,
            is_required: true,
            type: "text",
            is_multiple_value_allowed: true,
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata });
        expect(wrapper.contains("[data-test=document-custom-metadata-list-multiple]")).toBeFalsy();
    });

    it(`throws an event when list value is changed`, () => {
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

        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const wrapper = factory({ currentlyUpdatedItemMetadata });

        wrapper.vm.updateMultipleMetadataListValue();

        expect(event_bus_emit).toHaveBeenCalledWith("update-multiple-metadata-list-value", {
            detail: {
                value: wrapper.vm.multiple_list_values,
                id: "list",
            },
        });
    });
});
