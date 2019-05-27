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
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

describe("QuickLookDocumentAdditionalMetadataList", () => {
    let metadata_list_factory, state, store;

    beforeEach(() => {
        state = {};

        const store_options = { state };

        store = createStoreMock(store_options);

        metadata_list_factory = (props = {}) => {
            return shallowMount(QuickLookDocumentPreview, {
                localVue,
                propsData: { metadata: props },
                mocks: { $store: store }
            });
        };
    });
    describe(`Metadata name `, () => {
        it(`Given an Obsolescence Date metadata
             Then it displays "Validity" for the label`, () => {
            const metadata_date = {
                id: 100,
                name: "Obsolescence Date",
                type: "date",
                list_value: null,
                value: "2019-08-02"
            };
            store.state.date_time_format = "d/m/Y H:i";

            const wrapper = metadata_list_factory(metadata_date);

            const label_element = wrapper.find("[data-test=metadata-list-label]");
            expect(label_element).toBeTruthy();
            expect(label_element.text()).toBe("Validity");
        });
    });
    describe(`List type metadata `, () => {
        it(`Given a list value with several value
             Then it displays the list value in a ul balise`, () => {
            const metadata_list = {
                id: 100,
                name: "original name",
                type: "list",
                list_value: [
                    { id: 1, name: "value 1" },
                    { id: 2, name: "fail" },
                    { id: 3, name: "Tea" }
                ],
                value: ""
            };
            const wrapper = metadata_list_factory(metadata_list);

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
                value: ""
            };
            const wrapper = metadata_list_factory(metadata_list);

            expect(wrapper.contains("ul")).toBeFalsy();
            expect(wrapper.find("p").text()).toBe("value 1");
        });
    });
    describe("Date type metadata", () => {
        it(`Given a date
        Then it displays the formatted date`, () => {
            const metadata_date = {
                id: 100,
                name: "original date",
                type: "date",
                list_value: null,
                value: "2019-08-02"
            };
            store.state.date_time_format = "d/m/Y H:i";

            const wrapper = metadata_list_factory(metadata_date);
            expect(wrapper.contains("ul")).toBeFalsy();
            expect(wrapper.contains("[data-test=metadata-list-date]")).toBeTruthy();
            expect(
                wrapper.find("[data-tlp-tooltip]").element.getAttribute("data-tlp-tooltip")
            ).toBe("02/08/2019 00:00");
            expect(wrapper.text()).not.toEqual("Empty");
            expect(wrapper.text()).not.toEqual("Permanent");
            expect(wrapper.text()).not.toEqual(metadata_date.value);
        });
        it(`Given an obsolescence date
        Then it displays the formatted date like a regular date type metadata`, () => {
            const metadata_date = {
                id: 100,
                name: "Obsolescence Date",
                type: "date",
                list_value: null,
                value: "2019-08-02"
            };
            store.state.date_time_format = "d/m/Y H:i";

            const wrapper = metadata_list_factory(metadata_date);

            expect(wrapper.contains("ul")).toBeFalsy();
            expect(wrapper.contains("[data-test=metadata-list-date]")).toBeTruthy();
            expect(
                wrapper.find("[data-tlp-tooltip]").element.getAttribute("data-tlp-tooltip")
            ).toBe("02/08/2019 00:00");
            expect(wrapper.text()).not.toEqual("Empty");
            expect(wrapper.text()).not.toEqual("Permanent");
            expect(wrapper.text()).not.toEqual(metadata_date.value);
        });
        describe("Metadata simple string value", () => {
            it(`Given text type value
        Then it displays the value`, () => {
                const metadata_date = {
                    id: 100,
                    name: "Bad lyrics",
                    type: "text",
                    list_value: null,
                    value: "The mer-custo wants that ... mmmmmh, mmmmh ..."
                };

                const wrapper = metadata_list_factory(metadata_date);

                const displayed_metadata = wrapper.find("[id=document-bad-lyrics]");

                expect(wrapper.contains("ul")).toBeFalsy();
                expect(wrapper.contains("[data-test=metadata-list-date]")).toBeFalsy();
                expect(displayed_metadata).toBeTruthy();
                expect(displayed_metadata.text()).not.toEqual("Empty");
                expect(displayed_metadata.text()).not.toEqual("Permanent");
                expect(displayed_metadata.text()).toEqual(metadata_date.value);
            });
        });
        it(`Given text type empty value
        Then it displays the value`, () => {
            const metadata_date = {
                id: 100,
                name: "silence",
                type: "text",
                list_value: null,
                value: ""
            };

            const wrapper = metadata_list_factory(metadata_date);

            const displayed_metadata = wrapper.find("[id=document-silence]");

            expect(wrapper.contains("ul")).toBeFalsy();
            expect(wrapper.contains("[data-test=metadata-list-date]")).toBeFalsy();
            expect(displayed_metadata.text()).toBeTruthy();
            expect(displayed_metadata).not.toEqual("Permanent");
            expect(displayed_metadata.text()).toEqual("Empty");
            expect(displayed_metadata.text()).not.toEqual(metadata_date.value);
        });
    });
});
