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

import { shallowMount } from "@vue/test-utils";
import QuickLookDocumentPreview from "./QuickLookDocumentAdditionalMetadataList.vue";

import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

describe("QuickLookDocumentAdditionalMetadataList", () => {
    let metadata_factory, state, store;

    beforeEach(() => {
        state = {};

        const store_options = { state };

        store = createStoreMock(store_options);

        metadata_factory = (props = {}) => {
            return shallowMount(QuickLookDocumentPreview, {
                localVue,
                propsData: { metadata: props },
                mocks: { $store: store },
            });
        };
    });
    describe(`Metadata name`, () => {
        it(`Given an Obsolescence Date metadata
             Then it displays "Validity" for the label`, () => {
            const metadata_date = {
                id: 100,
                short_name: "obsolescence_date",
                name: "Obsolescence Date",
                type: "date",
                list_value: null,
                value: "2019-08-02",
                post_processed_value: "2019-08-02",
            };
            store.state.date_time_format = "d/m/Y H:i";

            const wrapper = metadata_factory(metadata_date);

            const label_element = wrapper.get("[data-test=metadata-list-label]");
            expect(label_element).toBeTruthy();
            expect(label_element.text()).toBe("Validity");
        });
    });

    describe(`List type metadata`, () => {
        it(`Given a list value with several value
             Then it displays the list value in a ul tag`, () => {
            const metadata_list = {
                id: 100,
                name: "original name",
                type: "list",
                list_value: [
                    { id: 1, name: "value 1" },
                    { id: 2, name: "fail" },
                    { id: 3, name: "Tea" },
                ],
                value: null,
                post_processed_value: null,
            };
            const wrapper = metadata_factory(metadata_list);

            const value_list_element = wrapper.findAll("li");

            expect(value_list_element.length).toBe(3);
            expect(value_list_element.at(0).text()).toBe("value 1");
            expect(value_list_element.at(1).text()).toBe("fail");
            expect(value_list_element.at(2).text()).toBe("Tea");
        });
        it(`Given a list value with one value
             Then it displays the value`, () => {
            const metadata_list = {
                id: 100,
                name: "original name",
                type: "list",
                list_value: [{ id: 1, name: "value 1" }],
                value: null,
                post_processed_value: null,
            };
            const wrapper = metadata_factory(metadata_list);

            expect(wrapper.contains("ul")).toBeFalsy();
            expect(wrapper.get("p").text()).toBe("value 1");
        });
    });

    describe("Metadata simple string value", () => {
        it(`Given text type value
    Then it displays the value`, () => {
            const metadata_string = {
                id: 100,
                name: "Bad lyrics",
                short_name: "bad-lyrics",
                type: "text",
                list_value: null,
                value: "The mer-custo wants ref #1 that ... mmmmmh, mmmmh ...",
                post_processed_value:
                    'The mer-custo wants <a href="https://example.com/goto">ref #1</a> that ... mmmmmh, mmmmh ...',
            };

            const wrapper = metadata_factory(metadata_string);

            const displayed_metadata = wrapper.get("[id=document-bad-lyrics]");

            expect(wrapper.contains("ul")).toBeFalsy();
            expect(wrapper.contains("[data-test=metadata-list-date]")).toBeFalsy();
            expect(displayed_metadata).toBeTruthy();
            expect(displayed_metadata.text()).toEqual(metadata_string.value);
            expect(displayed_metadata.html()).toContain(metadata_string.post_processed_value);
        });
    });
    it(`Given text type empty value
    Then it displays the value`, () => {
        const metadata_empty = {
            id: 100,
            name: "silence",
            short_name: "silence",
            type: "text",
            list_value: null,
            value: "",
            post_processed_value: "",
        };

        const wrapper = metadata_factory(metadata_empty);

        const displayed_metadata = wrapper.get("[id=document-silence]");

        expect(wrapper.contains("ul")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-list-date]")).toBeFalsy();
        expect(displayed_metadata.text()).toBeTruthy();
        expect(displayed_metadata).not.toEqual("Permanent");
        expect(displayed_metadata.text()).toEqual("Empty");
        expect(displayed_metadata.text()).not.toEqual(metadata_empty.value);
    });
});
