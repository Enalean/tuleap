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
import QuickLookDocumentProperties from "./QuickLookDocumentProperties.vue";
import localVue from "../../helpers/local-vue";

import { TYPE_FILE, TYPE_FOLDER } from "../../constants";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type {
    FileProperties,
    Folder,
    FolderProperty,
    Item,
    ItemFile,
    Property,
    User,
} from "../../type";

describe("QuickLookDocumentProperties", () => {
    let store = {};

    function createWrapper(item: Item): Wrapper<QuickLookDocumentProperties> {
        const store_options = {
            state: {
                configuration: {
                    date_time_format: "d/m/Y H:i",
                    relative_dates_display: true,
                    user_locale: "en_EN",
                },
            },
        };
        store = createStoreMock(store_options);

        return shallowMount(QuickLookDocumentProperties, {
            localVue,
            propsData: {
                item,
            },
            mocks: { $store: store },
            stubs: {
                "tlp-relative-date": true,
            },
        });
    }

    it(`Given document has multiple property
         Then they are displayed in two different columns`, () => {
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            } as User,
            properties: [
                { name: "custom", short_name: "custom property" } as Property,
                { name: "other", short_name: "other property" } as Property,
            ],
            creation_date: "2019-06-25T16:56:22+04:00",
        } as ItemFile;

        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test='properties-left-list']").exists()).toBeTruthy();
        expect(wrapper.find("[data-test='properties-right-list']").exists()).toBeTruthy();
    });

    it(`Given document has hardcoded property
         Then they are displayed in additional property`, () => {
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            } as User,
            properties: [{ name: "title document", short_name: "title" } as Property],
            creation_date: "2019-06-25T16:56:22+04:00",
        } as ItemFile;

        const wrapper = createWrapper(item);

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
            } as User,
            properties: [{ name: "title document", short_name: "title" } as FolderProperty],
        } as Folder;

        const wrapper = createWrapper(item);
        expect(wrapper.find("[data-test='properties-left-list']").exists()).toBeFalsy();
        expect(wrapper.find("[data-test='properties-right-list']").exists()).toBeFalsy();
    });

    it(`Given item is a file
         Then its size is displayed`, () => {
        const properties: Array<Property> = [];
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            } as User,
            file_properties: {
                file_size: 123456,
            } as FileProperties,
            creation_date: "2019-06-25T16:56:22+04:00",
            properties,
        } as ItemFile;

        const wrapper = createWrapper(item);
        expect(wrapper.find("[data-test='docman-file-size']").exists()).toBeTruthy();
    });

    it(`Given item has an approval table,
         Then its approval status is displayed`, () => {
        const properties: Array<Property> = [];
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            },
            has_approval_table: true,
            approval_table: {
                approval_state: "Approved",
            },
            creation_date: "2019-06-25T16:56:22+04:00",
            properties,
        } as ItemFile;

        const wrapper = createWrapper(item);
        expect(
            wrapper.find("[data-test='docman-item-approval-table-status-badge']").exists()
        ).toBeTruthy();
    });

    it(`Given item has no approval table,
         Then its approval status is never displayed`, () => {
        const properties: Array<Property> = [];
        const item = {
            id: 42,
            title: "file",
            type: TYPE_FILE,
            owner: {
                id: 102,
            },
            approval_table: null,
            creation_date: "2019-06-25T16:56:22+04:00",
            properties,
        } as ItemFile;

        const wrapper = createWrapper(item);
        expect(
            wrapper.find("[data-test='docman-item-approval-table-status-badge']").exists()
        ).toBeFalsy();
    });
});
