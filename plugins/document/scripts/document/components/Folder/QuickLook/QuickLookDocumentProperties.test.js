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
import QuickLookDocumentProperties from "./QuickLookDocumentProperties.vue";

import localVue from "../../../helpers/local-vue";
import { TYPE_FILE, TYPE_FOLDER } from "../../../constants";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

describe("QuickLookDocumentProperties", () => {
    let properties_factory;

    beforeEach(() => {
        const state = {
            configuration: {},
        };

        const store_options = {
            state,
        };

        const store = createStoreMock(store_options);

        properties_factory = (props = {}) => {
            return shallowMount(QuickLookDocumentProperties, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
                stubs: {
                    "tlp-relative-date": true,
                },
            });
        };

        store.state.configuration.date_time_format = "d/m/Y H:i";
    });

    it(`Given document has multiple property
         Then they are displayed in two different columns`, () => {
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            },
            properties: [
                { title: "custom", short_name: "custom property" },
                { title: "other", short_name: "other property" },
            ],
            creation_date: "2019-06-25T16:56:22+04:00",
        };

        const wrapper = properties_factory({ item });

        expect(wrapper.find("[data-test='properties-left-list']").exists()).toBeTruthy();
        expect(wrapper.find("[data-test='properties-right-list']").exists()).toBeTruthy();
    });

    it(`Given document has hardocded property
         Then they are displayed in additionnal property`, () => {
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            },
            properties: [{ title: "title document", short_name: "title" }],
            creation_date: "2019-06-25T16:56:22+04:00",
        };

        const wrapper = properties_factory({ item });

        expect(wrapper.find("[data-test='properties-left-list']").exists()).toBeFalsy();
        expect(wrapper.find("[data-test='properties-right-list']").exists()).toBeFalsy();
    });

    it(`Given folder,
         Then there is no additional (hardcoded + customize) property displayed`, () => {
        const item = {
            id: 42,
            title: "folder",
            type: TYPE_FOLDER,
            owner: {
                id: 102,
            },
            approval_table: null,
            properties: [{ title: "title document", short_name: "title" }],
        };

        const wrapper = properties_factory({ item });
        expect(wrapper.find("[data-test='properties-left-list']").exists()).toBeFalsy();
        expect(wrapper.find("[data-test='properties-right-list']").exists()).toBeFalsy();
    });

    it(`Given item is a file
         Then its size is displayed`, () => {
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            },
            file_properties: {
                file_size: 123456,
            },
            creation_date: "2019-06-25T16:56:22+04:00",
            properties: [],
        };

        const wrapper = properties_factory({ item });
        expect(wrapper.find("[data-test='docman-file-size']").exists()).toBeTruthy();
    });

    it(`Given item has an approval table,
         Then its approval status is displayed`, () => {
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            },
            approval_table: {
                approval_state: "Approved",
            },
            creation_date: "2019-06-25T16:56:22+04:00",
            properties: [],
        };

        const wrapper = properties_factory({ item });
        expect(
            wrapper.find("[data-test='docman-item-approval-table-status-badge']").exists()
        ).toBeTruthy();
    });

    it(`Given item has no approval table,
         Then its approval status is never displayed`, () => {
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            },
            approval_table: null,
            creation_date: "2019-06-25T16:56:22+04:00",
            properties: [],
        };

        const wrapper = properties_factory({ item });
        expect(
            wrapper.find("[data-test='docman-item-approval-table-status-badge']").exists()
        ).toBeFalsy();
    });
});
