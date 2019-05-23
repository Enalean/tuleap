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

import localVue from "../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import TitleMetadata from "./TitleMetadata.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import { TYPE_EMBEDDED, TYPE_FILE, TYPE_FOLDER } from "../../../constants.js";

describe("TitleMetadata", () => {
    let title_metadata_factory;
    beforeEach(() => {
        const state = {
            folder_content: [
                {
                    id: 42,
                    title: "Document title",
                    type: TYPE_FILE,
                    parent_id: 3
                }
            ]
        };

        const store_options = {
            state
        };

        const store = createStoreMock(store_options);

        title_metadata_factory = (props = {}) => {
            return shallowMount(TitleMetadata, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });

    it(`Given docman has several items
        When user enter an exiting item name
        Then a custom error is displayed`, async () => {
        const value = "";
        const type = TYPE_EMBEDDED;
        const parent = {
            id: 3,
            title: "test",
            type: TYPE_FOLDER
        };

        const wrapper = title_metadata_factory({
            value,
            type,
            parent
        });

        wrapper.setProps({ value: "Document title" });

        await wrapper.vm.$nextTick().then(() => {});
        expect(wrapper.contains("[data-test=title-error-message]")).toBeTruthy();
    });

    it(`Given docman has several items
        When user enter a folder with the same name than an item
        Then no error is displayed`, async () => {
        const value = "";
        const type = TYPE_FOLDER;
        const parent = {
            id: 3,
            title: "test",
            type: TYPE_FOLDER
        };

        const wrapper = title_metadata_factory({
            value,
            type,
            parent
        });

        wrapper.setProps({ value: "Document title" });

        await wrapper.vm.$nextTick().then(() => {});
        expect(wrapper.contains("[data-test=title-error-message]")).toBeFalsy();
    });

    it(`Given docman has several items
        When user enter a new item name
        Then no error is displayed`, async () => {
        const value = "";
        const type = TYPE_EMBEDDED;
        const parent = {
            id: 3,
            title: "test",
            type: TYPE_FOLDER
        };

        const wrapper = title_metadata_factory({
            value,
            type,
            parent
        });

        wrapper.setProps({ value: "An other document title" });

        await wrapper.vm.$nextTick().then(() => {});
        expect(wrapper.contains("[data-test=title-error-message]")).toBeFalsy();
    });
});
